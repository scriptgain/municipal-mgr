<?php

namespace App\Providers;

use App\Models\Setting;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\PublicLayoutComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Apply DB-backed settings over config at boot (the fleet's DB-driven
     * config pattern — secrets and operator knobs live in the DB, not .env),
     * and bind the view composers that keep logic out of Blade.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        // Composers supply every variable the layouts need, so the layout
        // templates contain markup only (no @php blocks, no queries in views).
        View::composer('components.layouts.app', AdminLayoutComposer::class);
        View::composer(['components.layouts.public', 'site.*'], PublicLayoutComposer::class);

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
            $s = Setting::map();

            // DB-driven timezone: meeting times and posting deadlines are legally
            // meaningful, so the whole app must render in the municipality's zone.
            if (! empty($s['timezone'])) {
                config(['app.timezone' => $s['timezone']]);
                date_default_timezone_set($s['timezone']);
            }
            if (! empty($s['brand_name'])) {
                config(['brand.name' => $s['brand_name'], 'app.name' => $s['brand_name']]);
            }
            if (! empty($s['brand_tagline'])) {
                config(['brand.tagline' => $s['brand_tagline']]);
            }
            if (! empty($s['brand_accent'])) {
                config(['brand.accent' => $s['brand_accent']]);
            }
            if (! empty($s['brand_accent_alt'])) {
                config(['brand.accent_alt' => $s['brand_accent_alt']]);
            }
            if (! empty($s['max_width'])) {
                config(['municipal.max_width' => $s['max_width']]);
            }
            if (! empty($s['session_timeout_minutes'])) {
                config(['session.lifetime' => (int) $s['session_timeout_minutes']]);
            }

            config([
                'municipal.date_format' => $s['date_format'] ?? 'M j, Y',
                'municipal.time_format' => $s['time_format'] ?? 'g:i A',
                'municipal.rows_per_page' => (int) ($s['rows_per_page'] ?? 25),
                'municipal.require_2fa' => ($s['require_2fa'] ?? '0') === '1',
                'municipal.force_password_days' => (int) ($s['force_password_days'] ?? 0),
                'municipal.audit_log_days' => (int) ($s['audit_log_days'] ?? 365),
            ]);

            // DB-driven SMTP: notification mail for service requests and forms.
            if (! empty($s['smtp_host'])) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $s['smtp_host'],
                    'mail.mailers.smtp.port' => (int) ($s['smtp_port'] ?: 587),
                    'mail.mailers.smtp.username' => $s['smtp_username'] ?? null,
                    'mail.mailers.smtp.password' => $s['smtp_password'] ?? null,
                    'mail.from.address' => $s['mail_from'] ?: ('noreply@' . parse_url((string) config('app.url'), PHP_URL_HOST)),
                    'mail.from.name' => $s['site_name'] ?? config('brand.name'),
                ]);
            }
        } catch (\Throwable $e) {
            // DB not ready (fresh install, pre-migrate) — fall back to config.
        }
    }
}
