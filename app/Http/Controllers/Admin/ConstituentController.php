<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Constituent;
use App\Models\ConstituentInteraction;
use App\Models\Department;
use App\Services\ConstituentTimeline;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The resident roll: the CRM half of a municipal site.
 *
 * Everything here is resident PII on a government system, so the whole
 * controller sits behind the staff auth gate and every read of a detail page
 * and every write is written to the audit log. Nothing in this controller has
 * a public counterpart, by design.
 */
class ConstituentController extends Controller implements HasMiddleware
{
    /**
     * Gate the entire controller on constituent access, so the read-only
     * "viewer" role cannot reach resident PII on any action. Writes keep their
     * stricter per-action isEditor() checks on top of this.
     */
    public static function middleware(): array
    {
        return [
            function (Request $request, \Closure $next) {
                abort_unless($request->user()?->canAccessConstituents(), 403);

                return $next($request);
            },
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Constituent::query()
            ->visibleTo($user)
            ->search($request->query('q'))
            ->withCount(['serviceRequests', 'formSubmissions', 'interactions']);

        if ($request->query('filter') === 'flagged') {
            $query->where('do_not_contact', true);
        } elseif ($request->query('filter') === 'unlinked') {
            // Residents on file who have never actually filed anything: usually
            // hand-entered counter contacts worth following up on.
            $query->doesntHave('serviceRequests')->doesntHave('formSubmissions');
        } elseif ($request->query('filter') === 'recent') {
            $query->where('last_interaction_at', '>=', now()->subDays(30));
        }

        if ($tag = trim((string) $request->query('tag'))) {
            $query->whereJsonContains('tags', $tag);
        }

        $sort = in_array($request->query('sort'), ['name', 'recent', 'activity'], true)
            ? $request->query('sort') : 'recent';

        match ($sort) {
            'name' => $query->orderBy('name'),
            'activity' => $query->orderByDesc('service_requests_count'),
            default => $query->orderByRaw('last_interaction_at IS NULL, last_interaction_at DESC'),
        };

        $records = $query->paginate((int) config('municipal.rows_per_page', 25))->withQueryString();

        return view('admin.constituents.index', [
            'records' => $records,
            'search' => $request->query('q'),
            'filter' => $request->query('filter', 'all'),
            'sort' => $sort,
            'tags' => $this->knownTags(),
            'stats' => $this->stats($user),
        ]);
    }

    public function create()
    {
        $this->authorizeWrite();

        return view('admin.constituents.create', [
            'record' => new Constituent(),
            'tags' => $this->knownTags(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeWrite();

        $data = $this->validated($request);
        $constituent = Constituent::create($data);

        AuditLog::record('created', "Constituent record created for {$constituent->name}", $constituent);

        return redirect()->route('constituents.show', $constituent)->with('status', 'Constituent Record Created.');
    }

    public function show(Request $request, Constituent $constituent)
    {
        $this->authorizeView($constituent);

        // Reading a resident's full history is itself an event worth recording
        // on a government system holding PII.
        AuditLog::record('viewed', "Viewed constituent record for {$constituent->name}", $constituent);

        $constituent->load('user');

        return view('admin.constituents.show', [
            'record' => $constituent,
            'timeline' => ConstituentTimeline::for($constituent),
            'counts' => ConstituentTimeline::counts($constituent),
            'requests' => $constituent->serviceRequests()->with('department')->latest()->get(),
            'submissions' => $constituent->formSubmissions()->with('form')->latest()->get(),
            'interactionTypes' => ConstituentInteraction::types(),
            'directions' => ConstituentInteraction::directions(),
            'departments' => Department::ordered()->get(['id', 'name']),
            'duplicates' => $constituent->duplicateCandidates(),
            // Prefilled "now" for the log-a-contact form, computed here so the
            // template stays markup only.
            'defaultOccurredAt' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function edit(Constituent $constituent)
    {
        $this->authorizeWrite();
        $this->authorizeView($constituent);

        return view('admin.constituents.edit', [
            'record' => $constituent,
            'tags' => $this->knownTags(),
        ]);
    }

    public function update(Request $request, Constituent $constituent)
    {
        $this->authorizeWrite();
        $this->authorizeView($constituent);

        $constituent->update($this->validated($request, $constituent));

        AuditLog::record('updated', "Constituent record updated for {$constituent->name}", $constituent);

        return redirect()->route('constituents.show', $constituent)->with('status', 'Constituent Record Updated.');
    }

    public function destroy(Constituent $constituent)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $name = $constituent->name;
        // Intake rows survive: deleting the resident record must not silently
        // erase the service requests the town is on the hook for. The foreign
        // key nulls out instead.
        $constituent->delete();

        AuditLog::record('deleted', "Constituent record deleted for {$name}", null);

        return redirect()->route('constituents.index')->with('status', 'Constituent Record Deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $n = $ids ? Constituent::whereIn('id', $ids)->delete() : 0;

        AuditLog::record('bulk-deleted', "{$n} constituent record(s) deleted in bulk");

        return back()->with('status', "{$n} Constituent Record(s) Deleted.");
    }

    /**
     * Merge a duplicate into this record.
     *
     * Residents file as "Bob Ruiz" one year and "Robert Ruiz jr" the next, from
     * two different email addresses, so duplicates are not an edge case here.
     * Everything moves to the surviving record and the loser is deleted; blank
     * fields on the survivor are filled from the duplicate, populated ones are
     * left alone.
     */
    public function merge(Request $request, Constituent $constituent)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'duplicate_id' => ['required', 'integer', 'different:' . $constituent->id, Rule::exists('constituents', 'id')],
        ]);

        $duplicate = Constituent::findOrFail($data['duplicate_id']);
        abort_if($duplicate->id === $constituent->id, 422);

        DB::transaction(function () use ($constituent, $duplicate) {
            $duplicate->serviceRequests()->update(['constituent_id' => $constituent->id]);
            $duplicate->formSubmissions()->update(['constituent_id' => $constituent->id]);
            ConstituentInteraction::where('constituent_id', $duplicate->id)
                ->update(['constituent_id' => $constituent->id]);

            $fill = [];
            foreach (['email', 'email_key', 'phone', 'phone_key', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'user_id'] as $field) {
                if (! $constituent->{$field} && $duplicate->{$field}) {
                    $fill[$field] = $duplicate->{$field};
                }
            }
            // Notes are additive: losing a note during a merge loses the only
            // copy of something a clerk wrote down.
            if ($duplicate->notes) {
                $fill['notes'] = trim(($constituent->notes ? $constituent->notes . "\n\n" : '')
                    . 'Merged from ' . $duplicate->name . ': ' . $duplicate->notes);
            }
            $tags = array_values(array_unique(array_merge($constituent->tagList(), $duplicate->tagList())));
            if ($tags) {
                $fill['tags'] = $tags;
            }
            if ($duplicate->last_interaction_at && (! $constituent->last_interaction_at
                || $duplicate->last_interaction_at->gt($constituent->last_interaction_at))) {
                $fill['last_interaction_at'] = $duplicate->last_interaction_at;
            }

            if ($fill) {
                $constituent->forceFill($fill)->save();
            }

            // Clear the unique email key first so deleting cannot collide.
            $duplicate->forceFill(['email_key' => null])->save();
            $duplicate->delete();
        });

        AuditLog::record('merged', "Constituent {$duplicate->name} (#{$duplicate->id}) merged into {$constituent->name}", $constituent);

        return redirect()->route('constituents.show', $constituent)
            ->with('status', 'Duplicate Record Merged.');
    }

    /** Log a phone call, counter visit, email, or letter against the record. */
    public function storeInteraction(Request $request, Constituent $constituent)
    {
        $this->authorizeWrite();
        $this->authorizeView($constituent);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ConstituentInteraction::types()))],
            'direction' => ['required', Rule::in(array_keys(ConstituentInteraction::directions()))],
            'subject' => ['nullable', 'string', 'max:200'],
            'note' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $data['constituent_id'] = $constituent->id;
        $data['user_id'] = auth()->id();

        $interaction = ConstituentInteraction::create($data);
        $constituent->touchInteraction($interaction->occurred_at);

        AuditLog::record('created', "Contact logged for {$constituent->name}: {$interaction->typeLabel()}", $constituent);

        // Explicit redirect, not back(): staff logging a call from the resident
        // page must land on that resident page, whatever referer the browser
        // happened to send.
        return redirect()->route('constituents.show', $constituent)->with('status', 'Contact Logged.');
    }

    public function destroyInteraction(Constituent $constituent, ConstituentInteraction $interaction)
    {
        $this->authorizeWrite();
        abort_unless($interaction->constituent_id === $constituent->id, 404);

        $interaction->delete();
        AuditLog::record('deleted', "Logged contact removed from {$constituent->name}", $constituent);

        return redirect()->route('constituents.show', $constituent)->with('status', 'Logged Contact Removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function validated(Request $request, ?Constituent $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:60'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'do_not_contact' => ['nullable', 'boolean'],
        ]);

        $data['email_key'] = Constituent::emailKey($data['email'] ?? null);
        $data['phone_key'] = Constituent::phoneKey($data['phone'] ?? null);
        $data['do_not_contact'] = $request->boolean('do_not_contact');
        $data['tags'] = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($data['tags'] ?? ''))
        )));

        // The email key is unique in the schema, so a clash means the record
        // already exists. Say so plainly instead of surfacing an SQL error.
        if ($data['email_key']) {
            $clash = Constituent::where('email_key', $data['email_key'])
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->first();

            if ($clash) {
                abort(redirect()->back()->withInput()->withErrors([
                    'email' => 'Another Constituent Already Uses That Email Address. Merge The Records Instead.',
                ]));
            }
        }

        return $data;
    }

    /** Viewers may read the roll but never change it. */
    private function authorizeWrite(): void
    {
        abort_unless(auth()->user()?->canEditContent(), 403);
    }

    /** Department editors may only open residents their department has dealt with. */
    private function authorizeView(Constituent $constituent): void
    {
        $visible = Constituent::query()->visibleTo(auth()->user())->whereKey($constituent->id)->exists();

        abort_unless($visible, 403);
    }

    private function knownTags(): array
    {
        return rescue(fn () => Constituent::whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn ($t) => (array) $t)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all(), [], false);
    }

    private function stats($user): array
    {
        $base = fn () => Constituent::query()->visibleTo($user);

        return [
            'total' => $base()->count(),
            'recent' => $base()->where('last_interaction_at', '>=', now()->subDays(30))->count(),
            'flagged' => $base()->where('do_not_contact', true)->count(),
            'unlinked' => $base()->doesntHave('serviceRequests')->doesntHave('formSubmissions')->count(),
        ];
    }
}
