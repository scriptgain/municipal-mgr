<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Services\ConstituentIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function show(FormDefinition $formDefinition)
    {
        abort_unless($formDefinition->is_published, 404);

        return view('site.forms.show', [
            'form' => $formDefinition,
            'fields' => $formDefinition->fieldList(),
        ]);
    }

    public function submit(Request $request, FormDefinition $formDefinition)
    {
        abort_unless($formDefinition->is_published, 404);

        $rules = $formDefinition->validationRules();
        $rules['website'] = ['nullable', 'size:0']; // honeypot
        $validated = $request->validate($rules);

        $payload = $validated['fields'] ?? [];

        if ($formDefinition->store_submissions) {
            $submission = FormSubmission::create([
                'form_definition_id' => $formDefinition->id,
                'data' => $payload,
                'ip' => $request->ip(),
            ]);

            // Attach the submission to the resident's record when the form
            // captured something durable to identify them by. Never allowed to
            // break the submission itself.
            rescue(fn () => ConstituentIntake::fromFormSubmission($submission), null, false);
        }

        if ($formDefinition->notify_email) {
            $lines = [];
            foreach ($formDefinition->fieldList() as $f) {
                $v = $payload[$f['key']] ?? '';
                $lines[] = $f['label'] . ': ' . (is_array($v) ? implode(', ', $v) : $v);
            }
            // Plain text: it reaches every clerk's mail client intact, and a
            // failed notification must never lose the stored submission above.
            rescue(fn () => Mail::raw(
                implode("\n", $lines),
                fn ($m) => $m->to($formDefinition->notify_email)
                    ->subject('New Submission: ' . $formDefinition->name)
            ), null, false);
        }

        return redirect()->route('site.forms.show', $formDefinition)
            ->with('form_success', $formDefinition->success_message
                ?: 'Thank You. Your Submission Has Been Received.');
    }
}
