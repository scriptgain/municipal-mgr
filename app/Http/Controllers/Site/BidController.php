<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Bid;

class BidController extends Controller
{
    public function index()
    {
        return view('site.bids.index', [
            'open' => Bid::published()->open()->with('department', 'document')->orderBy('closes_at')->get(),
            'closed' => Bid::published()->whereIn('status', ['closed', 'awarded'])
                ->orderByDesc('closes_at')->paginate(20),
        ]);
    }

    public function show(Bid $bid)
    {
        abort_unless($bid->is_published, 404);

        return view('site.bids.show', ['bid' => $bid->load('department', 'document')]);
    }
}
