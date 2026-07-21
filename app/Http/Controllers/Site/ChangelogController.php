<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;

/**
 * The public Changelog / What's New page.
 *
 * Reads only published entries, newest first, and renders each Markdown body
 * to HTML in the model so the view stays markup-only.
 */
class ChangelogController extends Controller
{
    public function index()
    {
        seo()->set([
            'title' => 'What\'s New',
            'description' => 'Release notes for the municipal website platform. See what has recently changed and what is coming next.',
        ]);

        return view('site.changelog.index', [
            'entries' => ChangelogEntry::published()->newestFirst()->get(),
        ]);
    }
}
