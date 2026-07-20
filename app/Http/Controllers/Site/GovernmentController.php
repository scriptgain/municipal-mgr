<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Official;

class GovernmentController extends Controller
{
    public function index()
    {
        return view('site.government.index', [
            'officials' => Official::published()->current()->orderBy('sort_order')->get(),
            'former' => Official::published()->where('is_current', false)->orderByDesc('term_end')->limit(20)->get(),
            'meetings' => Meeting::published()->upcoming()->limit(4)->get(),
        ]);
    }

    public function show(Official $official)
    {
        abort_unless($official->is_published, 404);

        return view('site.government.show', ['official' => $official]);
    }
}
