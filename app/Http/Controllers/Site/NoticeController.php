<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $current = Notice::current()->with('department', 'document')
            ->orderByDesc('posted_at')->paginate(20)->withQueryString();

        // Expired notices stay reachable: a statutory posting is a record, and
        // people cite them long after they come down.
        $expired = Notice::where('status', 'published')
            ->whereNotNull('expires_at')->where('expires_at', '<', now())
            ->orderByDesc('expires_at')->limit(25)->get();

        return view('site.notices.index', compact('current', 'expired'));
    }

    public function show(Notice $notice)
    {
        abort_unless($notice->status === 'published' || auth()->user()?->canEditContent(), 404);

        return view('site.notices.show', ['notice' => $notice->load('department', 'document')]);
    }
}
