<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ArrestRecord;
use App\Services\RecordsSettings;
use Illuminate\Http\Request;

/**
 * Public arrest blotter and inmate roster.
 *
 * Every read goes through ArrestRecord::public(), which is the single place
 * that knows what "publishable" means (published, in retention, adult). The
 * views receive finished values; they contain no queries and no conditionals
 * beyond presence checks.
 */
class RecordsController extends Controller
{
    public function index(Request $request)
    {
        $settings = RecordsSettings::all();
        $searchEnabled = $settings['records_public_search_enabled'] === '1';

        $range = (string) $request->query('range', '30');
        $ranges = config('records.blotter_ranges', []);
        if (! array_key_exists($range, $ranges)) {
            $range = '30';
        }

        $query = ArrestRecord::public()->with('charges');

        if ($range !== 'all') {
            $query->where('booked_at', '>=', now()->subDays((int) $range)->startOfDay());
        }

        $term = $searchEnabled ? trim((string) $request->query('q')) : '';
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('last_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('case_number', 'like', $like)
                    ->orWhere('arresting_agency', 'like', $like);
            });
        }

        $records = $query->orderByDesc('booked_at')
            ->paginate(20)
            ->withQueryString();

        return view('site.records.index', [
            'records' => $records,
            'range' => $range,
            'ranges' => $ranges,
            'search' => $term,
            'searchEnabled' => $searchEnabled,
            'disclaimer' => RecordsSettings::disclaimer(),
            'intro' => $settings['records_blotter_intro'],
            'agencyName' => $settings['records_agency_name'],
            'takedownContact' => $settings['records_takedown_contact'],
            'retentionDays' => RecordsSettings::retentionDays(),
            'showBond' => $settings['records_show_bond'] === '1',
            'showCaseNumber' => $settings['records_show_case_number'] === '1',
            'rosterEnabled' => RecordsSettings::rosterEnabled(),
            'rosterCount' => ArrestRecord::public()->inCustody()->count(),
        ]);
    }

    public function roster()
    {
        abort_unless(RecordsSettings::rosterEnabled(), 404);

        $settings = RecordsSettings::all();

        return view('site.records.roster', [
            'records' => ArrestRecord::public()->inCustody()->with('charges')
                ->orderByDesc('booked_at')->paginate(50),
            'disclaimer' => RecordsSettings::disclaimer(),
            'agencyName' => $settings['records_agency_name'],
            'takedownContact' => $settings['records_takedown_contact'],
            'showBond' => $settings['records_show_bond'] === '1',
            'showCaseNumber' => $settings['records_show_case_number'] === '1',
        ]);
    }

    public function show(string $ref)
    {
        // findOrFail through the public scope, so an unpublished, expired, or
        // juvenile record is a 404 even to someone holding its direct link.
        $record = ArrestRecord::public()->with('charges')->where('public_ref', $ref)->firstOrFail();

        $settings = RecordsSettings::all();

        return view('site.records.show', [
            'record' => $record,
            'disclaimer' => RecordsSettings::disclaimer(),
            'agencyName' => $settings['records_agency_name'],
            'takedownContact' => $settings['records_takedown_contact'],
            'showBond' => $settings['records_show_bond'] === '1',
            'showCaseNumber' => $settings['records_show_case_number'] === '1',
            'retentionDays' => RecordsSettings::retentionDays(),
        ]);
    }
}
