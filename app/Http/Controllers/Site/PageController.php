<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        // Staff previewing their own draft is fine; the public gets a 404.
        abort_unless($page->isPublished() || auth()->user()?->canEditContent(), 404);

        $page->load(['department', 'children']);

        seo()->for($page);

        return view('site.page', [
            'page' => $page,
            'blocks' => $page->blocks(),
            'trail' => $page->trail(),
        ]);
    }
}
