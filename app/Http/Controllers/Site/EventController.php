<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::published()->with('department');

        $past = $request->query('when') === 'past';
        $past
            ? $query->where('starts_at', '<', now())->orderByDesc('starts_at')
            : $query->upcoming();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return view('site.events.index', [
            'events' => $query->paginate(15)->withQueryString(),
            'categories' => Event::published()->distinct()->orderBy('category')->pluck('category'),
            'activeCategory' => $category,
            'showingPast' => $past,
        ]);
    }

    /**
     * Month grid. The calendar itself is built here (not in the view) so the
     * template stays pure markup — weeks come through pre-assembled.
     */
    public function calendar(Request $request)
    {
        $month = Carbon::createFromDate(
            (int) $request->query('year', now()->year),
            (int) $request->query('month', now()->month),
            1
        )->startOfMonth();

        $events = Event::published()
            ->whereBetween('starts_at', [$month->copy()->startOfWeek(), $month->copy()->endOfMonth()->endOfWeek()])
            ->orderBy('starts_at')->get()
            ->groupBy(fn (Event $e) => $e->starts_at->toDateString());

        $cursor = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        $weeks = [];
        $week = [];

        while ($cursor <= $end) {
            $week[] = [
                'date' => $cursor->copy(),
                'day' => $cursor->day,
                'in_month' => $cursor->month === $month->month,
                'is_today' => $cursor->isToday(),
                'events' => $events->get($cursor->toDateString(), collect()),
            ];
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
            $cursor->addDay();
        }

        return view('site.events.calendar', [
            'month' => $month,
            'weeks' => $weeks,
            'prev' => $month->copy()->subMonth(),
            'next' => $month->copy()->addMonth(),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->is_published || auth()->user()?->canEditContent(), 404);

        seo()->for($event);

        return view('site.events.show', ['event' => $event->load('department')]);
    }
}
