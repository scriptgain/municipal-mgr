<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Seo\SeoSettings;
use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Site-wide SEO defaults: what every page falls back to, the verification
 * tags, and the staging switch. Stored in the DB Setting store, not .env.
 */
class SeoSettingsController extends Controller
{
    public function edit(SitemapBuilder $sitemap)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        return view('settings.seo', [
            'seo' => SeoSettings::all(),
            'sitemapUrl' => route('sitemap.index'),
            'robotsUrl' => route('robots'),
            'sitemapCounts' => $sitemap->counts(),
            'sitemapTotal' => $sitemap->totalUrls(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isEditor(), 403);

        $data = $request->validate([
            'seo_title_template' => ['nullable', 'string', 'max:100'],
            'seo_default_title' => ['nullable', 'string', 'max:255'],
            'seo_default_description' => ['nullable', 'string', 'max:500'],
            'seo_google_verification' => ['nullable', 'string', 'max:255'],
            'seo_bing_verification' => ['nullable', 'string', 'max:255'],
            'seo_pinterest_verification' => ['nullable', 'string', 'max:255'],
            'seo_twitter_site' => ['nullable', 'string', 'max:60'],
            'seo_organization_type' => ['nullable', 'string', 'max:60'],
            'og_image' => ['nullable', 'image', 'max:8192'],
        ]);

        if ($request->hasFile('og_image')) {
            $file = $request->file('og_image');
            $name = 'og-default-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension();
            $data['seo_default_og_image'] = Storage::disk('public')->putFileAs('site', $file, $name);
        }
        unset($data['og_image']);

        // Toggle switches post 1/0 through a hidden input.
        foreach (['seo_discourage', 'seo_structured_data', 'seo_sitemap_enabled'] as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        SeoSettings::put($data);

        // Worth its own audit line: flipping this hides the whole site from
        // search, and "why did we drop out of Google" should be answerable.
        AuditLog::record(
            'updated',
            'SEO settings updated' . ($data['seo_discourage'] === '1' ? ' (search engines DISCOURAGED)' : '')
        );

        return back()->with('status', 'SEO Settings Saved.');
    }
}
