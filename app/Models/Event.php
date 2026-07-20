<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use Auditable, HasSeo, HasSlug;

    protected $fillable = [
        'department_id', 'title', 'slug', 'category', 'description', 'starts_at',
        'ends_at', 'all_day', 'location', 'address', 'registration_url',
        'image_path', 'is_published',
        'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'bool',
            'is_published' => 'bool',
            'noindex' => 'bool',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    public function scopeInMonth(Builder $q, int $year, int $month): Builder
    {
        $start = now()->setDate($year, $month, 1)->startOfMonth();

        return $q->whereBetween('starts_at', [$start, $start->copy()->endOfMonth()]);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** "Aug 14, 2026, 6:00 PM to 8:00 PM" / "Aug 14, 2026 (All Day)". */
    public function whenDisplay(): string
    {
        $d = config('municipal.date_format', 'M j, Y');
        $t = config('municipal.time_format', 'g:i A');

        if ($this->all_day) {
            return $this->starts_at->format($d) . ' (All Day)';
        }

        $out = $this->starts_at->format($d . ', ' . $t);
        if ($this->ends_at) {
            $out .= ' to ' . ($this->ends_at->isSameDay($this->starts_at)
                ? $this->ends_at->format($t)
                : $this->ends_at->format($d . ', ' . $t));
        }

        return $out;
    }

    /** Keeps the strip_tags check out of the view, per the no-logic rule. */
    public function hasDescription(): bool
    {
        return trim(strip_tags((string) $this->description)) !== '';
    }

    /**
     * Where this event sits relative to now. Drives the status pill.
     * Returns [label, tone].
     */
    public function statusBadge(): array
    {
        $end = $this->ends_at ?: $this->starts_at;

        if ($end->isPast()) {
            return ['Past Event', 'slate'];
        }

        if ($this->starts_at->isToday()) {
            return ['Happening Today', 'emerald'];
        }

        if ($this->starts_at->isTomorrow()) {
            return ['Tomorrow', 'seal'];
        }

        return [$this->starts_at->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 1]) . ' Away', 'brand'];
    }

    /**
     * Add-to-calendar via a plain Google Calendar URL rather than a generated
     * .ics file: no route, no controller, no MIME wrangling, and it works on
     * the phone most residents will be holding.
     */
    public function googleCalendarUrl(): string
    {
        $fmt = fn (\Illuminate\Support\Carbon $d) => $this->all_day
            ? $d->format('Ymd')
            : $d->utc()->format('Ymd\THis\Z');

        $end = $this->ends_at ?: ($this->all_day
            ? $this->starts_at->copy()->addDay()
            : $this->starts_at->copy()->addHour());

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title,
            'dates' => $fmt($this->starts_at) . '/' . $fmt($end),
            'details' => \Illuminate\Support\Str::limit(strip_tags((string) $this->description), 900),
            'location' => trim($this->location . ' ' . $this->address),
        ]);
    }

    /** Outlook.com / Microsoft 365 web calendar. */
    public function outlookCalendarUrl(): string
    {
        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $this->title,
            'startdt' => $this->starts_at->utc()->toIso8601String(),
            'enddt' => $this->calendarEnd()->utc()->toIso8601String(),
            'body' => $this->calendarDetails(),
            'location' => $this->calendarLocation(),
            'allday' => $this->all_day ? 'true' : 'false',
        ]);
    }

    public function yahooCalendarUrl(): string
    {
        return 'https://calendar.yahoo.com/?' . http_build_query([
            'v' => 60,
            'title' => $this->title,
            'st' => $this->starts_at->utc()->format('Ymd\THis\Z'),
            'et' => $this->calendarEnd()->utc()->format('Ymd\THis\Z'),
            'desc' => $this->calendarDetails(),
            'in_loc' => $this->calendarLocation(),
        ]);
    }

    /**
     * Apple Calendar and desktop Outlook both want a real .ics file.
     *
     * Served as a data URI rather than a route: it needs no controller, no MIME
     * configuration, and nothing to keep in sync if the event changes. The
     * download attribute on the link gives it a sensible filename.
     */
    public function icsDataUri(): string
    {
        $fmt = fn (\Illuminate\Support\Carbon $d) => $d->utc()->format('Ymd\THis\Z');
        $esc = fn (string $v) => str_replace(["\\", "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], trim($v));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ScriptGain//MunicipalMGR//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-' . $this->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($this->starts_at),
            'DTEND:' . $fmt($this->calendarEnd()),
            'SUMMARY:' . $esc($this->title),
            'DESCRIPTION:' . $esc($this->calendarDetails()),
            'LOCATION:' . $esc($this->calendarLocation()),
            'URL:' . route('site.events.show', $this->slug),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return 'data:text/calendar;charset=utf-8,' . rawurlencode(implode("\r\n", $lines));
    }

    /** Filename offered when the .ics is downloaded. */
    public function icsFilename(): string
    {
        return \Illuminate\Support\Str::slug($this->title ?: 'event') . '.ics';
    }

    private function calendarEnd(): \Illuminate\Support\Carbon
    {
        return $this->ends_at ?: ($this->all_day
            ? $this->starts_at->copy()->addDay()
            : $this->starts_at->copy()->addHour());
    }

    private function calendarDetails(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags((string) $this->description), 900);
    }

    private function calendarLocation(): string
    {
        return trim($this->location . ' ' . $this->address);
    }

    /** Directions link, only when there is an address worth mapping. */
    public function mapsUrl(): ?string
    {
        $where = trim($this->address ?: '');

        if ($where === '') {
            return null;
        }

        return 'https://www.google.com/maps/search/?' . http_build_query([
            'api' => 1,
            'query' => trim($this->location . ' ' . $where),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* SEO                                                                 */
    /* ------------------------------------------------------------------ */

    protected function seoRouteName(): ?string
    {
        return 'site.events.show';
    }

    public function seoSchemaType(): ?string
    {
        return 'Event';
    }

    protected function seoDescriptionSources(): array
    {
        return ['description'];
    }

    protected function seoImageSources(): array
    {
        return ['og_image', 'image_path'];
    }
}
