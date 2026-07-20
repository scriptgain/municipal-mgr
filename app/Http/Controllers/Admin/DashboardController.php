<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Bid;
use App\Models\Event;
use App\Models\FileItem;
use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\Page;
use App\Models\ServiceRequest;

/**
 * Staff landing screen. Answers the four questions a clerk actually opens the
 * panel to answer: what needs my attention, what is going out publicly soon,
 * what did residents just send us, and what is stale.
 */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $requests = ServiceRequest::query();

        $stats = [
            'open_requests' => (clone $requests)->open()->count(),
            'unassigned_requests' => (clone $requests)->open()->whereNull('department_id')->count(),
            'overdue_requests' => (clone $requests)->open()->where('created_at', '<', now()->subDays(7))->count(),
            'unread_submissions' => FormSubmission::whereNull('read_at')->count(),
        ];

        $content = [
            'pages' => Page::where('status', 'published')->count(),
            'drafts' => Page::where('status', '!=', 'published')->count(),
            'documents' => FileItem::publiclyVisible()->count(),
            'news' => NewsPost::where('status', 'published')->count(),
        ];

        // What the public will see change in the next fortnight.
        $upcomingMeetings = Meeting::published()->upcoming()->limit(5)->get();
        $upcomingEvents = Event::published()->upcoming()->limit(5)->get();

        $recentRequests = ServiceRequest::with('department')
            ->latest()->limit(8)->get();

        $recentSubmissions = FormSubmission::with('form')
            ->latest()->limit(5)->get();

        // Things that quietly go stale and embarrass a municipality.
        $attention = [
            'expiring_notices' => Notice::current()->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(7))->count(),
            'closing_bids' => Bid::published()->open()->whereNotNull('closes_at')
                ->where('closes_at', '<=', now()->addDays(7))->count(),
            'closing_jobs' => JobPosting::open()->whereNotNull('closes_at')
                ->where('closes_at', '<=', now()->addDays(7))->count(),
            'meetings_missing_minutes' => Meeting::published()->past()
                ->whereNull('minutes_document_id')->where('meets_at', '>=', now()->subMonths(6))->count(),
        ];

        $liveAlert = Alert::live()->get()->sortByDesc(fn (Alert $a) => $a->weight())->first();

        // 14-day intake trend for the sparkline, bucketed in PHP so it behaves
        // the same on SQLite and MySQL.
        $since = now()->subDays(13)->startOfDay();
        $recent = ServiceRequest::where('created_at', '>=', $since)->get(['created_at', 'status']);
        $activity = collect(range(0, 13))->map(function ($i) use ($recent) {
            $day = now()->subDays(13 - $i)->startOfDay();
            $next = $day->copy()->addDay();
            $onDay = $recent->filter(fn ($r) => $r->created_at >= $day && $r->created_at < $next);

            return [
                'label' => $day->format('M j'),
                'total' => $onDay->count(),
                'resolved' => $onDay->whereIn('status', ['resolved', 'closed'])->count(),
            ];
        })->all();

        return view('admin.dashboard', compact(
            'stats', 'content', 'upcomingMeetings', 'upcomingEvents',
            'recentRequests', 'recentSubmissions', 'attention', 'liveAlert', 'activity',
        ));
    }
}
