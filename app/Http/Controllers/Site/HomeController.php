<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\NewsPost;
use App\Models\Notice;

class HomeController extends Controller
{
    public function index()
    {
        return view('site.home', [
            'featured' => NewsPost::published()->where('is_featured', true)->latestFirst()->first(),
            'news' => NewsPost::published()->with('department')->latestFirst()->limit(6)->get(),
            'events' => Event::published()->upcoming()->limit(5)->get(),
            'meetings' => Meeting::published()->upcoming()->with('agenda')->limit(4)->get(),
            'notices' => Notice::current()->orderByDesc('posted_at')->limit(4)->get(),
        ]);
    }
}
