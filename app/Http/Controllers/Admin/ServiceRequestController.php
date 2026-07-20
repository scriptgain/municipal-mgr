<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestUpdate;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Staff triage for resident-submitted issues.
 *
 * Not an AdminController subclass: requests are never "created" by staff, and
 * the write path is a status transition plus a note, not a form save.
 */
class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ServiceRequest::with(['department', 'assignee'])
            ->search($request->query('q'));

        // Department editors triage only their own department's queue.
        if ($user->isDepartmentEditor()) {
            $query->where('department_id', $user->department_id);
        }

        if ($status = $request->query('status')) {
            $status === 'open' ? $query->open() : $query->where('status', $status);
        }
        if ($department = $request->query('department')) {
            $query->where('department_id', $department);
        }

        $records = $query->latest()->paginate((int) config('municipal.rows_per_page', 25))->withQueryString();

        // Tab counts, so staff can see the shape of the queue without clicking.
        $counts = [
            'open' => ServiceRequest::open()->count(),
            'new' => ServiceRequest::where('status', 'new')->count(),
            'in_progress' => ServiceRequest::whereIn('status', ['assigned', 'in_progress'])->count(),
            'resolved' => ServiceRequest::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.service-requests.index', [
            'records' => $records,
            'counts' => $counts,
            'search' => $request->query('q'),
            'status' => $request->query('status', 'open'),
            'departments' => Department::ordered()->get(['id', 'name']),
            'statuses' => config('municipal.request_statuses'),
        ]);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['department', 'assignee', 'updatesLog.user', 'constituent']);

        return view('admin.service-requests.show', [
            'record' => $serviceRequest,
            'departments' => Department::ordered()->get(['id', 'name']),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => config('municipal.request_statuses'),
        ]);
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(config('municipal.request_statuses')))],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $was = $serviceRequest->status;

        // Timestamps are derived from the transition, never hand-entered: they
        // are the numbers a council member will ask about at budget time.
        if ($was === 'new' && $data['status'] !== 'new' && ! $serviceRequest->acknowledged_at) {
            $data['acknowledged_at'] = now();
        }
        if (in_array($data['status'], ['resolved', 'closed'], true) && ! $serviceRequest->resolved_at) {
            $data['resolved_at'] = now();
        }
        if (! in_array($data['status'], ['resolved', 'closed'], true)) {
            $data['resolved_at'] = null;
        }

        $serviceRequest->update($data);

        if ($was !== $data['status']) {
            $serviceRequest->updatesLog()->create([
                'user_id' => auth()->id(),
                'status' => $data['status'],
                'note' => 'Status changed to ' . $serviceRequest->statusLabel() . '.',
                'is_public' => true,
            ]);
        }

        AuditLog::record('updated', "Service request {$serviceRequest->reference} updated", $serviceRequest);

        return back()->with('status', 'Request ' . $serviceRequest->reference . ' Updated.');
    }

    /** Add a note to the trail. Internal notes stay off the resident's page. */
    public function addUpdate(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        ServiceRequestUpdate::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => auth()->id(),
            'status' => $serviceRequest->status,
            'note' => $data['note'],
            'is_public' => $request->boolean('is_public'),
        ]);

        return back()->with('status', 'Note Added.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        abort_unless(auth()->user()->isEditor(), 403);
        $ref = $serviceRequest->reference;
        $serviceRequest->delete();

        return redirect()->route('service-requests.index')->with('status', "Request {$ref} Deleted.");
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $n = $ids ? ServiceRequest::whereIn('id', $ids)->delete() : 0;
        AuditLog::record('bulk-deleted', "{$n} service request(s) deleted in bulk");

        return back()->with('status', "{$n} Request(s) Deleted.");
    }
}
