<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArrestRecord;
use App\Models\AuditLog;
use App\Services\RecordsSettings;
use Illuminate\Http\Request;

/**
 * The only part of the module that exists while the module is off.
 *
 * Enabling is a deliberate act with consequences the operator is told about in
 * plain language before they flip the switch, and the flip itself is audited.
 */
class RecordsSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isEditor(), 403);

        return view('settings.records', [
            'settings' => RecordsSettings::all(),
            'enabled' => RecordsSettings::enabled(),
            'defaultDisclaimer' => RecordsSettings::DEFAULT_DISCLAIMER,
            'minimumAge' => (int) config('records.minimum_publish_age', 18),
            'publishedCount' => $this->publishedCount(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'records_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'records_disclaimer' => ['required', 'string', 'max:2000'],
            'records_blotter_intro' => ['nullable', 'string', 'max:1000'],
            'records_takedown_contact' => ['nullable', 'string', 'max:200'],
            'records_agency_name' => ['nullable', 'string', 'max:150'],
        ]);

        $was = RecordsSettings::enabled();
        $mugshotsWere = RecordsSettings::mugshotsEnabled();

        $data['records_module_enabled'] = $request->boolean('records_module_enabled') ? '1' : '0';
        $data['records_mugshots_enabled'] = $request->boolean('records_mugshots_enabled') ? '1' : '0';
        $data['records_show_bond'] = $request->boolean('records_show_bond') ? '1' : '0';
        $data['records_show_case_number'] = $request->boolean('records_show_case_number') ? '1' : '0';
        $data['records_public_search_enabled'] = $request->boolean('records_public_search_enabled') ? '1' : '0';
        $data['records_roster_enabled'] = $request->boolean('records_roster_enabled') ? '1' : '0';

        RecordsSettings::put($data);

        $now = $data['records_module_enabled'] === '1';
        RecordsSettings::syncPublicMenu($now);

        if ($was !== $now) {
            AuditLog::record(
                $now ? 'module-enabled' : 'module-disabled',
                $now
                    ? 'Jail And Arrest Records module ENABLED. Public blotter and inmate roster are now reachable.'
                    : 'Jail And Arrest Records module DISABLED. Public blotter and inmate roster now return not-found.'
            );
        }

        if ($mugshotsWere !== ($data['records_mugshots_enabled'] === '1')) {
            AuditLog::record(
                'updated',
                'Arrest record mugshot publication ' . ($data['records_mugshots_enabled'] === '1' ? 'ENABLED' : 'DISABLED')
            );
        }

        AuditLog::record('updated', 'Jail And Arrest Records settings updated');

        return back()->with('status', 'Arrest Records Settings Saved.');
    }

    /** Zero when the table has not been migrated yet, rather than a 500. */
    private function publishedCount(): int
    {
        return rescue(fn () => ArrestRecord::public()->count(), 0, false);
    }
}
