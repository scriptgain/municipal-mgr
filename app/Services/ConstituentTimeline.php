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
            ->filter(fn (array $e) => $e['at'] !== null)
            ->sortByDesc(fn (array $e) => $e['at']->getTimestamp())
            ->values();
    }

    private static function serviceRequests(Constituent $constituent): Collection
    {
        return $constituent->serviceRequests()->with('department')->get()->map(fn ($r) => [
            'kind' => 'Service Request',
            'icon' => 'bolt',
            'title' => $r->reference . ' — ' . $r->category,
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
