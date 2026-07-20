<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormSubmissionController extends Controller
{
    public function index(Request $request, FormDefinition $formDefinition)
    {
        $records = $formDefinition->submissions()
            ->latest()
            ->paginate((int) config('municipal.rows_per_page', 25));

        return view('admin.forms.submissions', [
            'form' => $formDefinition,
            'records' => $records,
            'columns' => collect($formDefinition->fieldList())->take(4)->all(),
        ]);
    }

    public function show(FormSubmission $formSubmission)
    {
        $formSubmission->load(['form', 'constituent']);
        if ($formSubmission->isUnread()) {
            $formSubmission->update(['read_at' => now()]);
        }

        return view('admin.forms.submission', [
            'record' => $formSubmission,
            'fields' => $formSubmission->form?->fieldList() ?? [],
        ]);
    }

    /** CSV export — the format every clerk already knows how to open. */
    public function export(FormDefinition $formDefinition)
    {
        $fields = $formDefinition->fieldList();
        $name = Str::slug($formDefinition->name) . '-submissions-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($formDefinition, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Submitted'], array_column($fields, 'label')));

            $formDefinition->submissions()->oldest()->chunk(200, function ($chunk) use ($out, $fields) {
                foreach ($chunk as $row) {
                    $line = [$row->created_at?->toDateTimeString()];
                    foreach ($fields as $f) {
                        $v = $row->data[$f['key']] ?? '';
                        $line[] = is_array($v) ? implode(', ', $v) : (string) $v;
                    }
                    fputcsv($out, $line);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function destroy(FormSubmission $formSubmission)
    {
        $form = $formSubmission->form;
        $formSubmission->delete();

        return redirect()->route('forms.submissions.index', $form)->with('status', 'Submission Deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $n = $ids ? FormSubmission::whereIn('id', $ids)->delete() : 0;

        return back()->with('status', "{$n} Submission(s) Deleted.");
    }
}
