<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $body = $request->query('body');
        $scope = fn ($q) => $q->published()->with(['agenda', 'minutes', 'packet'])
            ->when($body, fn ($s) => $s->where('body', $body));

        return view('site.meetings.index', [
            'upcoming' => $scope(Meeting::query())->upcoming()->get(),
            'past' => $scope(Meeting::query())->past()->paginate(20)->withQueryString(),
            'bodies' => Meeting::published()->distinct()->orderBy('body')->pluck('body'),
            'activeBody' => $body,
        ]);
    }

    public function show(Meeting $meeting)
    {
        abort_unless($meeting->is_published || auth()->user()?->canEditContent(), 404);

        return view('site.meetings.show', [
            'meeting' => $meeting->load(['agenda', 'minutes', 'packet']),
            'related' => Meeting::published()->where('body', $meeting->body)
                ->where('id', '!=', $meeting->id)->orderByDesc('meets_at')->limit(6)->get(),
        ]);
    }
}
