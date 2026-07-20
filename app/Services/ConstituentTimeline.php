<?php

namespace App\Services;

use App\Models\Constituent;
use Illuminate\Support\Collection;

/**
 * The one chronological view of everything a resident has ever filed or been
 * contacted about: service requests, form submissions, and staff-logged
 * contact, merged and sorted newest first.
 *
 * Lives in a service, not the view, because the merge is real logic and the
 * Blade template is markup only.
 */
class ConstituentTimeline
{
    /**
     * @return Collection<int, array{
     *     kind: string, icon: string, title: string, summary: string,
     *     at: \Illuminate\Support\Carbon, meta: array<int, string>,
     *     href: ?string, badge: ?array{label: string, color: string}, actor: ?string
     * }>
     */
    public static function for(Constituent $constituent): Collection
    {
        return collect()
            ->concat(self::serviceRequests($constituent))
            ->concat(self::formSubmissions($constituent))
            ->concat(self::interactions($constituent))
            ->concat(self::bills($constituent))
            ->concat(self::payments($constituent))
            ->filter(fn (array $e) => $e['at'] !== null)
            ->sortByDesc(fn (array $e) => $e['at']->getTimestamp())
            ->values();
    }

    private static function serviceRequests(Constituent $constituent): Collection
    {
        return $constituent->serviceRequests()->with('department')->get()->map(fn ($r) => [
            'kind' => 'Service Request',
            'icon' => 'bolt',
            'title' => $r->reference . ': ' . $r->category,
            'summary' => (string) $r->description,
            'at' => $r->created_at,
            'meta' => array_values(array_filter([
                $r->location_text,
                $r->department?->name,
            ])),
            'href' => route('service-requests.show', $r),
            'badge' => ['label' => $r->statusLabel(), 'color' => $r->statusColor()],
            'actor' => null,
        ]);
    }

    private static function formSubmissions(Constituent $constituent): Collection
    {
        return $constituent->formSubmissions()->with('form')->get()->map(function ($s) {
            $fields = $s->form?->fieldList() ?? [];
            $lines = [];
            foreach ($fields as $field) {
                $value = $s->data[$field['key']] ?? null;
                if ($value === null || $value === '' || is_array($value)) {
                    continue;
                }
                $lines[] = $field['label'] . ': ' . $value;
                if (count($lines) >= 3) {
                    break;
                }
            }

            return [
                'kind' => 'Form Submission',
                'icon' => 'clipboard',
                'title' => $s->form?->name ?? 'Form Submission',
                'summary' => implode("\n", $lines),
                'at' => $s->created_at,
                'meta' => [],
                'href' => route('submissions.show', $s),
                'badge' => $s->isUnread() ? ['label' => 'Unread', 'color' => 'warn'] : null,
                'actor' => null,
            ];
        });
    }

    private static function interactions(Constituent $constituent): Collection
    {
        return $constituent->interactions()->with(['user', 'department'])->get()->map(fn ($i) => [
            'kind' => $i->typeLabel(),
            'icon' => $i->typeIcon(),
            'title' => $i->subject ?: $i->typeLabel(),
            'summary' => (string) $i->note,
            'at' => $i->occurred_at,
            'meta' => array_values(array_filter([
                $i->directionLabel(),
                $i->department?->name,
            ])),
            'href' => null,
            'badge' => ['label' => 'Staff Logged', 'color' => 'neutral'],
            'actor' => $i->user?->name,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Payments module
    |--------------------------------------------------------------------------
    | Queried directly rather than through a relation on Constituent, so the
    | payments module adds itself to the resident timeline without editing the
    | Constituent model.
    |
    | Every method below is guarded: the payments module ships disabled and its
    | tables may not exist at all on an install that has never switched it on.
    | A resident's timeline must not 500 because of a module the town does not
    | use.
    */

    /** Bills raised against this resident. */
    private static function bills(Constituent $constituent): Collection
    {
        if (! self::paymentsAvailable()) {
            return collect();
        }

        return \App\Models\Bill::with('type')
            ->where('constituent_id', $constituent->id)
            ->get()
            ->map(fn ($bill) => [
                'kind' => 'Bill Issued',
                'icon' => 'file-text',
                'title' => $bill->reference . ': ' . ($bill->type?->label ?? 'Bill'),
                'summary' => trim(($bill->description ?: '') . "\n" . $bill->amountFormatted() . ': ' . $bill->dueLabel()),
                'at' => $bill->created_at,
                'meta' => array_values(array_filter([
                    $bill->account_number ? 'Account ' . $bill->account_number : null,
                    $bill->balanceCents() > 0 ? $bill->balanceFormatted() . ' outstanding' : 'Settled',
                ])),
                'href' => route('bills.show', $bill),
                'badge' => ['label' => $bill->isOverdue() ? 'Overdue' : $bill->statusLabel(), 'color' => $bill->statusColor()],
                'actor' => null,
            ]);
    }

    /** Payments this resident has made, online or at the counter. */
    private static function payments(Constituent $constituent): Collection
    {
        if (! self::paymentsAvailable()) {
            return collect();
        }

        return \App\Models\Payment::with(['bill', 'type', 'recorder'])
            ->where('constituent_id', $constituent->id)
            ->whereIn('status', ['succeeded', 'refunded', 'partially_refunded'])
            ->get()
            ->map(fn ($payment) => [
                'kind' => 'Payment',
                'icon' => 'database',
                'title' => $payment->amountFormatted() . ': ' . ($payment->bill?->type?->label ?? $payment->type?->label ?? 'Payment'),
                'summary' => trim(($payment->bill ? 'Against bill ' . $payment->bill->reference . '. ' : '')
                    . 'Paid by ' . $payment->instrumentLabel() . '. Reference ' . $payment->reference . '.'
                    . ($payment->isTestPayment() ? ' TEST PAYMENT: no real money was taken.' : '')),
                'at' => $payment->paid_at ?? $payment->created_at,
                'meta' => array_values(array_filter([
                    $payment->refunded_cents > 0 ? $payment->refundedFormatted() . ' refunded' : null,
                    $payment->isTestPayment() ? 'Test Mode' : null,
                ])),
                'href' => route('payments.show', $payment),
                'badge' => ['label' => $payment->statusLabel(), 'color' => $payment->statusColor()],
                'actor' => $payment->recorder?->name,
            ]);
    }

    /**
     * Is the payments module actually installed and switched on?
     *
     * Checks the gate AND the table, because the two can disagree: a database
     * restored from before the module was added will have the setting but not
     * the tables.
     */
    private static function paymentsAvailable(): bool
    {
        return rescue(
            fn () => \App\Services\Payments\PaymentSettings::isEnabled()
                && \Illuminate\Support\Facades\Schema::hasTable('payments'),
            false,
            false
        );
    }

    /** Headline counts for the detail page tab strip. */
    public static function counts(Constituent $constituent): array
    {
        return [
            'requests' => $constituent->serviceRequests()->count(),
            'submissions' => $constituent->formSubmissions()->count(),
            'interactions' => $constituent->interactions()->count(),
        ];
    }
}
