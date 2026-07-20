<?php

use App\Models\Constituent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds constituent records out of the reporter details already sitting in
 * service_requests and form_submissions, then links those rows back.
 *
 * Written against the query builder rather than Eloquent on purpose: the models
 * fire audit events, and backfilling a few thousand historic rows would bury
 * the real audit trail under machine noise. One summary line is recorded
 * instead by the seeder/operator.
 *
 * Rollback: `down()` clears every constituent_id it set and deletes only the
 * constituents it created (source = 'backfill'). Rows staff created by hand
 * afterwards, and anything linked to them, survive a rollback untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('constituents')) {
            return;
        }

        $this->backfillServiceRequests();
        $this->backfillFormSubmissions();
        $this->refreshLastInteraction();
    }

    public function down(): void
    {
        if (! Schema::hasTable('constituents')) {
            return;
        }

        $ids = DB::table('constituents')->where('source', 'backfill')->pluck('id');

        if (Schema::hasColumn('service_requests', 'constituent_id')) {
            DB::table('service_requests')->whereIn('constituent_id', $ids)->update(['constituent_id' => null]);
        }
        if (Schema::hasColumn('form_submissions', 'constituent_id')) {
            DB::table('form_submissions')->whereIn('constituent_id', $ids)->update(['constituent_id' => null]);
        }
        if (Schema::hasTable('constituent_interactions')) {
            DB::table('constituent_interactions')->whereIn('constituent_id', $ids)->delete();
        }

        DB::table('constituents')->whereIn('id', $ids)->delete();
    }

    private function backfillServiceRequests(): void
    {
        if (! Schema::hasTable('service_requests') || ! Schema::hasColumn('service_requests', 'constituent_id')) {
            return;
        }

        DB::table('service_requests')
            ->whereNull('constituent_id')
            ->where('is_anonymous', false)
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $id = $this->resolveId([
                        'name' => $row->reporter_name,
                        'email' => $row->reporter_email,
                        'phone' => $row->reporter_phone,
                        'address_line1' => null,
                    ], $row->created_at);

                    if ($id) {
                        DB::table('service_requests')->where('id', $row->id)->update(['constituent_id' => $id]);
                    }
                }
            });
    }

    private function backfillFormSubmissions(): void
    {
        if (! Schema::hasTable('form_submissions') || ! Schema::hasColumn('form_submissions', 'constituent_id')) {
            return;
        }

        DB::table('form_submissions')
            ->whereNull('constituent_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $data = json_decode((string) $row->data, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $id = $this->resolveId(self::harvest($data), $row->created_at);

                    if ($id) {
                        DB::table('form_submissions')->where('id', $row->id)->update(['constituent_id' => $id]);
                    }
                }
            });
    }

    /**
     * Pull name/email/phone/address out of a free-form submission payload.
     *
     * Form keys are author-defined, so there is no schema to lean on. Matching
     * on the key name is imperfect by nature: a field literally called
     * "contact" is ambiguous and is deliberately skipped rather than guessed
     * at, because a wrong guess merges two residents into one record.
     */
    private static function harvest(array $data): array
    {
        $out = ['name' => null, 'email' => null, 'phone' => null, 'address_line1' => null, 'city' => null, 'postal_code' => null];

        foreach ($data as $key => $value) {
            if (is_array($value) || $value === null || trim((string) $value) === '') {
                continue;
            }
            $value = trim((string) $value);
            $k = strtolower((string) $key);

            if (! $out['email'] && (str_contains($k, 'email') || str_contains($k, 'e_mail'))) {
                $out['email'] = $value;
            } elseif (! $out['email'] && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $out['email'] = $value;
            }

            if (! $out['phone'] && (str_contains($k, 'phone') || str_contains($k, 'mobile') || str_contains($k, 'telephone'))) {
                $out['phone'] = $value;
            }

            if (! $out['name'] && (str_contains($k, 'name') || $k === 'full_name') && ! str_contains($k, 'user') && ! str_contains($k, 'business')) {
                $out['name'] = $value;
            }

            if (! $out['address_line1'] && (str_contains($k, 'address') || str_contains($k, 'street'))) {
                $out['address_line1'] = $value;
            }
            if (! $out['city'] && $k === 'city') {
                $out['city'] = $value;
            }
            if (! $out['postal_code'] && (str_contains($k, 'zip') || str_contains($k, 'postal'))) {
                $out['postal_code'] = $value;
            }
        }

        return $out;
    }

    /** Find-or-create by the same normalized keys the live intake path uses. */
    private function resolveId(array $attributes, ?string $seenAt): ?int
    {
        $emailKey = Constituent::emailKey($attributes['email'] ?? null);
        $phoneKey = Constituent::phoneKey($attributes['phone'] ?? null);

        if (! $emailKey && ! $phoneKey) {
            return null;
        }

        $existing = null;
        if ($emailKey) {
            $existing = DB::table('constituents')->where('email_key', $emailKey)->first();
        }
        if (! $existing && $phoneKey) {
            $existing = DB::table('constituents')->where('phone_key', $phoneKey)->first();
        }

        if ($existing) {
            $this->enrich($existing, $attributes, $emailKey, $phoneKey);

            return (int) $existing->id;
        }

        $name = trim((string) ($attributes['name'] ?? ''));

        return (int) DB::table('constituents')->insertGetId([
            'name' => $name !== '' ? $name : ($attributes['email'] ?? $attributes['phone'] ?? 'Unnamed Resident'),
            'email' => $emailKey ? trim((string) $attributes['email']) : null,
            'email_key' => $emailKey,
            'phone' => $phoneKey ? trim((string) $attributes['phone']) : null,
            'phone_key' => $phoneKey,
            'address_line1' => $attributes['address_line1'] ?? null,
            'city' => $attributes['city'] ?? null,
            'postal_code' => $attributes['postal_code'] ?? null,
            'source' => 'backfill',
            'last_interaction_at' => $seenAt,
            'created_at' => $seenAt ?: now(),
            'updated_at' => now(),
        ]);
    }

    /** Fill blanks only. Never overwrite a value already on the record. */
    private function enrich(object $existing, array $attributes, ?string $emailKey, ?string $phoneKey): void
    {
        $update = [];

        if (! $existing->email && $emailKey) {
            $update['email'] = trim((string) $attributes['email']);
            $update['email_key'] = $emailKey;
        }
        if (! $existing->phone && $phoneKey) {
            $update['phone'] = trim((string) $attributes['phone']);
            $update['phone_key'] = $phoneKey;
        }
        foreach (['address_line1', 'city', 'postal_code'] as $field) {
            $value = trim((string) ($attributes[$field] ?? ''));
            if (empty($existing->{$field}) && $value !== '') {
                $update[$field] = $value;
            }
        }

        if ($update) {
            $update['updated_at'] = now();
            DB::table('constituents')->where('id', $existing->id)->update($update);
        }
    }

    /** Set last_interaction_at to the newest thing actually on file. */
    private function refreshLastInteraction(): void
    {
        DB::table('constituents')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $stamps = [];

                if (Schema::hasColumn('service_requests', 'constituent_id')) {
                    $stamps[] = DB::table('service_requests')->where('constituent_id', $row->id)->max('created_at');
                }
                if (Schema::hasColumn('form_submissions', 'constituent_id')) {
                    $stamps[] = DB::table('form_submissions')->where('constituent_id', $row->id)->max('created_at');
                }
                if (Schema::hasTable('constituent_interactions')) {
                    $stamps[] = DB::table('constituent_interactions')->where('constituent_id', $row->id)->max('occurred_at');
                }

                $stamps = array_filter($stamps);
                if ($stamps) {
                    DB::table('constituents')->where('id', $row->id)->update(['last_interaction_at' => max($stamps)]);
                }
            }
        });
    }
};
