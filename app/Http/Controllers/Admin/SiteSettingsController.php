<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Public-site identity: who the municipality is, how to reach it, and what the
 * homepage hero says. Stored in the DB Setting store, not .env (fleet rule).
 */
class SiteSettingsController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->isEditor(), 403);

        return view('settings.site', [
            'site' => SiteSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'site_kind' => ['nullable', 'string', 'max:40'],
            'site_state' => ['nullable', 'string', 'max:60'],
            'site_motto' => ['nullable', 'string', 'max:200'],
            'site_hero_heading' => ['nullable', 'string', 'max:200'],
            'site_hero_subheading' => ['nullable', 'string', 'max:400'],
            'contact_address' => ['nullable', 'string', 'max:200'],
            'contact_city_state_zip' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_fax' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_hours' => ['nullable', 'string', 'max:200'],
            'contact_after_hours' => ['nullable', 'string', 'max:200'],
            'contact_map_embed' => ['nullable', 'string', 'max:2000'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_x' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_nextdoor' => ['nullable', 'url', 'max:255'],
            'footer_note' => ['nullable', 'string', 'max:500'],
            'accessibility_contact' => ['nullable', 'string', 'max:200'],
            'pay_bill_url' => ['nullable', 'url', 'max:255'],
            'meeting_stream_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'seal' => ['nullable', 'image', 'max:4096'],
            'hero' => ['nullable', 'image', 'max:12288'],
        ]);

        foreach (['logo' => 'site_logo_path', 'seal' => 'site_seal_path', 'hero' => 'site_hero_image_path'] as $field => $key) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $name = $field . '-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension();
                $data[$key] = Storage::disk('public')->putFileAs('site', $file, $name);
            }
            unset($data[$field]);
        }

        SiteSettings::put($data);
        AuditLog::record('updated', 'Site identity settings updated');

        return back()->with('status', 'Site Settings Saved.');
    }
}
