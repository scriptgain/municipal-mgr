<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceRequest;
use App\Services\IntegrationNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Report An Issue — the resident-facing intake and status tracker.
 *
 * Deliberately account-free. A resident reporting a broken streetlight will not
 * create a password to do it, and requiring one simply means the streetlight
 * goes unreported. The tracking token in the URL is the credential.
 */
class ServiceRequestController extends Controller
{
    public function create()
    {
        return view('site.report.create', [
            'categories' => config('municipal.request_categories'),
            'departments' => Department::published()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:4000'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'reporter_name' => ['nullable', 'string', 'max:150'],
            'reporter_email' => ['nullable', 'email', 'max:150'],
            'reporter_phone' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'max:8192'],
            // Honeypot: bots fill it, humans never see it.
            'website' => ['nullable', 'size:0'],
        ], [
            'description.min' => 'Please Describe The Issue In A Little More Detail.',
        ]);

        unset($data['website']);
        $data['is_anonymous'] = $request->boolean('is_anonymous');
        $data['ip'] = $request->ip();

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = 'report-' . Str::lower(Str::random(10)) . '.' . $file->getClientOriginalExtension();
            $data['photo_path'] = Storage::disk('public')->putFileAs('reports', $file, $name);
        }

        // Anonymous means anonymous: contact details are not retained at all.
        if ($data['is_anonymous']) {
            $data['reporter_name'] = null;
            $data['reporter_email'] = null;
            $data['reporter_phone'] = null;
        }

        $serviceRequest = ServiceRequest::create($data);

        $serviceRequest->updatesLog()->create([
            'status' => 'new',
            'note' => 'Request received.',
            'is_public' => true,
        ]);

        rescue(fn () => IntegrationNotifier::notify(
            'New Service Request ' . $serviceRequest->reference,
            $serviceRequest->category . ' — ' . Str::limit($serviceRequest->description, 200)
        ), null, false);

        return redirect()->route('site.report.submitted', $serviceRequest->tracking_token);
    }

    public function submitted(string $token)
    {
        $record = ServiceRequest::where('tracking_token', $token)->firstOrFail();

        return view('site.report.submitted', ['record' => $record]);
    }

    public function trackForm()
    {
        return view('site.report.track');
    }

    /** Look a request up by reference + the email that filed it. */
    public function track(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        $record = ServiceRequest::where('reference', trim($data['reference']))
            ->where('reporter_email', trim($data['email']))
            ->first();

        if (! $record) {
            return back()->withInput()->withErrors([
                'reference' => 'No Request Matches That Reference And Email Address.',
            ]);
        }

        return redirect()->route('site.report.status', $record->tracking_token);
    }

    public function status(string $token)
    {
        $record = ServiceRequest::with(['publicUpdates', 'department'])
            ->where('tracking_token', $token)->firstOrFail();

        return view('site.report.status', ['record' => $record]);
    }
}
