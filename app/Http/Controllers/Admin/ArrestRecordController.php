<?php

namespace App\Http\Controllers\Admin;

use App\Models\ArrestCharge;
use App\Models\ArrestRecord;
use App\Models\AuditLog;
use App\Models\ExpungementLog;
use App\Services\RecordsSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Staff management of arrest records.
 *
 * Inherits the shared index/CRUD/massSelect behaviour but overrides index()
 * for the filter set this screen needs (date window, custody, disposition,
 * published state) and adds the three actions no other module has: publish,
 * unpublish, and expunge.
 *
 * Expunge is deliberately NOT the delete button. Delete is "this record was
 * entered wrong". Expunge is "a court ordered this destroyed", it destroys the
 * mugshot file as well as the row, and it writes a compliance entry that
 * outlives the record.
 */
class ArrestRecordController extends AdminController
{
    protected string $model = ArrestRecord::class;
    protected string $views = 'arrest-records';
    protected string $routes = 'arrest-records';
    protected string $label = 'Arrest Record';
    protected array $with = ['charges'];
    protected array $searchable = ['last_name', 'first_name', 'case_number', 'booking_number', 'arresting_agency'];
    protected array $orderBy = ['booked_at', 'desc'];
    protected bool $departmentScoped = false;

    public function index(Request $request)
    {
        $query = $this->scoped()->with($this->with);

        if ($term = trim((string) $request->query('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $query->where(function ($q) use ($like) {
                foreach ($this->searchable as $i => $col) {
                    $i === 0 ? $q->where($col, 'like', $like) : $q->orWhere($col, 'like', $like);
                }
            });
        }

        $filters = [
            'custody' => (string) $request->query('custody', ''),
            'disposition' => (string) $request->query('disposition', ''),
            'state' => (string) $request->query('state', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        if ($filters['custody'] !== '') {
            $query->where('custody_status', $filters['custody']);
        }
        if ($filters['disposition'] !== '') {
            $query->where('disposition', $filters['disposition']);
        }
        if ($filters['from'] !== '') {
            $query->where('booked_at', '>=', $filters['from'] . ' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $query->where('booked_at', '<=', $filters['to'] . ' 23:59:59');
        }

        match ($filters['state']) {
            'published' => $query->where('is_published', true),
            'unpublished' => $query->where('is_published', false),
            'expired' => $query->retentionLapsed(),
            'juvenile' => $query->where('age', '<', (int) config('records.minimum_publish_age', 18)),
            default => null,
        };

        $records = $query->orderByDesc('booked_at')
            ->paginate((int) config('municipal.rows_per_page', 25))
            ->withQueryString();

        return view('admin.arrest-records.index', [
            'records' => $records,
            'search' => $request->query('q'),
            'filters' => $filters,
            'counts' => [
                'total' => ArrestRecord::count(),
                'in_custody' => ArrestRecord::inCustody()->count(),
                'published' => ArrestRecord::where('is_published', true)->count(),
                'lapsed' => ArrestRecord::retentionLapsed()->count(),
            ],
            'retentionDays' => RecordsSettings::retentionDays(),
            'mugshotsEnabled' => RecordsSettings::mugshotsEnabled(),
        ] + $this->formData());
    }

    /** Current custody list, the staff-side mirror of the public roster. */
    public function roster()
    {
        return view('admin.arrest-records.roster', [
            'records' => ArrestRecord::inCustody()->with('charges')
                ->orderByDesc('booked_at')
                ->paginate((int) config('municipal.rows_per_page', 25)),
        ]);
    }

    /** The compliance trail. Read only: nothing may edit or delete an entry. */
    public function expungements()
    {
        return view('admin.arrest-records.expungements', [
            'records' => ExpungementLog::with('performer')
                ->orderByDesc('performed_at')
                ->paginate((int) config('municipal.rows_per_page', 25)),
        ]);
    }

    public function create()
    {
        return view('admin.arrest-records.create', [
            'record' => new ArrestRecord,
            'chargeRows' => $this->chargeRows(null),
        ] + $this->formData());
    }

    public function edit(Request $request, string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        return view('admin.arrest-records.edit', [
            'record' => $record->load('charges'),
            'chargeRows' => $this->chargeRows($record),
        ] + $this->formData());
    }

    /**
     * Charge rows as JSON for the Alpine repeater, old() input winning so a
     * failed validation does not throw away a clerk's typing.
     *
     * Built here rather than in the view: assembling this in Blade would mean
     * a json_encode and a null-coalesce chain in a template, and views in this
     * product hold markup only.
     */
    private function chargeRows(?ArrestRecord $record): string
    {
        $old = old('charges');

        if (is_array($old)) {
            $rows = collect($old)
                ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
                ->map(fn ($row) => [
                    'description' => (string) ($row['description'] ?? ''),
                    'statute' => (string) ($row['statute'] ?? ''),
                    'severity' => (string) ($row['severity'] ?? 'misdemeanor'),
                    'counts' => (int) ($row['counts'] ?? 1),
                ])->values();
        } elseif ($record && $record->exists) {
            $rows = $record->charges->map(fn (ArrestCharge $c) => [
                'description' => $c->description,
                'statute' => (string) $c->statute,
                'severity' => $c->severity,
                'counts' => $c->counts,
            ])->values();
        } else {
            $rows = collect();
        }

        return $rows->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
    }

    protected function formData(): array
    {
        return [
            'dispositions' => config('records.dispositions', []),
            'custodyStatuses' => config('records.custody_statuses', []),
            'severities' => config('records.charge_severities', []),
            'minimumAge' => (int) config('records.minimum_publish_age', 18),
            'mugshotPolicyOn' => RecordsSettings::mugshotsEnabled(),
            'severitiesJson' => json_encode(
                collect(config('records.charge_severities', []))->map(fn ($s) => $s['label'])->all(),
                JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG
            ),
            'retentionDays' => RecordsSettings::retentionDays(),
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'booked_at' => ['required', 'date'],
            'released_at' => ['nullable', 'date', 'after_or_equal:booked_at'],
            'arresting_agency' => ['required', 'string', 'max:150'],
            'case_number' => ['nullable', 'string', 'max:80'],
            'booking_number' => ['nullable', 'string', 'max:80'],
            'bond_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'bond_note' => ['nullable', 'string', 'max:150'],
            'custody_status' => ['required', 'in:' . implode(',', array_keys(config('records.custody_statuses', [])))],
            'disposition' => ['required', 'in:' . implode(',', array_keys(config('records.dispositions', [])))],
            'disposition_note' => ['nullable', 'string', 'max:200'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'mugshot_takedown_note' => ['nullable', 'string', 'max:200'],
            'mugshot' => ['nullable', 'image', 'max:6144'],
            'charges' => ['array', 'max:25'],
            'charges.*.description' => ['nullable', 'string', 'max:200'],
            'charges.*.statute' => ['nullable', 'string', 'max:80'],
            'charges.*.severity' => ['nullable', 'in:' . implode(',', array_keys(config('records.charge_severities', [])))],
            'charges.*.counts' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $wantsPublish = $request->boolean('is_published');
        $minimum = (int) config('records.minimum_publish_age', 18);
        $age = $data['age'] ?? null;

        // Juvenile block, stated plainly rather than silently ignored. The
        // model enforces this too; this exists so the staff member is told why.
        if ($wantsPublish && $age !== null && $age < $minimum) {
            throw ValidationException::withMessages([
                'is_published' => "This Subject Is Under {$minimum}. Juvenile Arrest Records Cannot Be Published. The Record Has Been Saved Unpublished So It Remains Available To Staff.",
            ]);
        }

        $data['is_published'] = $wantsPublish;
        $data['mugshot_takedown_requested'] = $request->boolean('mugshot_takedown_requested');

        unset($data['charges'], $data['mugshot']);

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        if ($path = $this->storeUpload($request, 'mugshot', 'records/mugshots')) {
            $record->forceFill(['mugshot_path' => $path])->saveQuietly();
        }

        if ($request->boolean('remove_mugshot')) {
            $this->deleteMugshot($record);
            $record->forceFill(['mugshot_path' => null])->saveQuietly();
        }

        $this->syncCharges($record, $request);
    }

    /** Rewrite the charge list from the form's repeating rows. */
    private function syncCharges(Model $record, Request $request): void
    {
        if (! $request->has('charges')) {
            return;
        }

        $rows = collect((array) $request->input('charges'))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
            ->values();

        $record->charges()->delete();

        foreach ($rows as $i => $row) {
            ArrestCharge::create([
                'arrest_record_id' => $record->getKey(),
                'description' => trim((string) $row['description']),
                'statute' => $row['statute'] ?? null,
                'severity' => $row['severity'] ?? 'misdemeanor',
                'counts' => max(1, (int) ($row['counts'] ?? 1)),
                'sort_order' => $i,
            ]);
        }
    }

    /* ------------------------------------------------------------------
     * Publication state
     * ---------------------------------------------------------------- */

    public function publish(string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        if ($record->isJuvenile()) {
            return back()->with('warning', 'Juvenile Records Cannot Be Published.');
        }

        $record->fill(['is_published' => true, 'unpublish_reason' => null])->save();
        AuditLog::record('published', "Arrest record {$record->reference()} published to the public blotter", $record);

        return back()->with('status', 'Record Published To The Public Blotter.');
    }

    public function unpublish(Request $request, string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        $reason = trim((string) $request->input('unpublish_reason')) ?: null;
        $record->fill(['is_published' => false, 'unpublish_reason' => $reason])->save();
        AuditLog::record('unpublished', "Arrest record {$record->reference()} removed from the public blotter" . ($reason ? " ({$reason})" : ''), $record);

        return back()->with('status', 'Record Removed From The Public Blotter.');
    }

    /* ------------------------------------------------------------------
     * Expungement
     * ---------------------------------------------------------------- */

    /**
     * Carry out a sealing or expungement order.
     *
     * Hard delete of the record, its charges (cascade), and the mugshot file on
     * disk. What survives is an ExpungementLog entry proving the order was
     * executed and an audit-log line naming the staff member who did it:      * neither of which contains the subject's name.
     */
    public function expunge(Request $request, string $key)
    {
        $record = $this->findOrFail($key);
        $this->authorizeRecord($record);

        $data = $request->validate([
            'order_reference' => ['nullable', 'string', 'max:120'],
            'ordered_by' => ['required', 'string', 'max:150'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reference = $record->reference();
        $hadMugshot = (bool) $record->mugshot_path;
        $this->deleteMugshot($record);

        ExpungementLog::create([
            'case_number' => $record->case_number,
            'booking_number' => $record->booking_number,
            'order_reference' => $data['order_reference'] ?? null,
            'ordered_by' => $data['ordered_by'],
            'reason' => $data['reason'] ?? null,
            'performed_by' => auth()->id(),
            'performed_by_name' => auth()->user()?->name,
            'performed_at' => now(),
            'ip' => $request->ip(),
            'mugshot_destroyed' => $hadMugshot,
        ]);

        // deleteQuietly: the model's generic "deleted" audit line would be
        // redundant next to the explicit expungement entry below.
        $record->charges()->delete();
        $record->deleteQuietly();

        AuditLog::record(
            'expunged',
            "Arrest record {$reference} EXPUNGED by order of {$data['ordered_by']}"
                . (! empty($data['order_reference']) ? " (order {$data['order_reference']})" : '')
                . '. Record and mugshot destroyed.'
        );

        return redirect()->route('arrest-records.index')
            ->with('status', 'Record Expunged. The Public Record And Mugshot Were Destroyed And The Order Was Logged.');
    }

    private function deleteMugshot(Model $record): void
    {
        if ($record->mugshot_path) {
            rescue(fn () => Storage::disk('public')->delete($record->mugshot_path), null, false);
        }
    }
}
