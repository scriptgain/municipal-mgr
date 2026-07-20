<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\HostSslController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Site;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
| The municipality's actual website. These routes are deliberately OUTSIDE
| every auth/setup/license gate: a government site must stay reachable even
| when the panel is mid-setup or the license cannot be verified.
*/
Route::name('site.')->group(function () {
    Route::get('/', [Site\HomeController::class, 'index'])->name('home');

    Route::get('/news', [Site\NewsController::class, 'index'])->name('news');
    Route::get('/news/{newsPost}', [Site\NewsController::class, 'show'])->name('news.show');

    Route::get('/notices', [Site\NoticeController::class, 'index'])->name('notices');
    Route::get('/notices/{notice}', [Site\NoticeController::class, 'show'])->name('notices.show');

    Route::get('/events', [Site\EventController::class, 'index'])->name('events');
    Route::get('/events/{event}', [Site\EventController::class, 'show'])->name('events.show');
    Route::get('/calendar', [Site\EventController::class, 'calendar'])->name('calendar');

    Route::get('/departments', [Site\DepartmentController::class, 'index'])->name('departments');
    Route::get('/departments/{department}', [Site\DepartmentController::class, 'show'])->name('departments.show');

    Route::get('/directory', [Site\DirectoryController::class, 'index'])->name('directory');
    Route::get('/government', [Site\GovernmentController::class, 'index'])->name('government');
    Route::get('/government/{official}', [Site\GovernmentController::class, 'show'])->name('government.show');

    Route::get('/meetings', [Site\MeetingController::class, 'index'])->name('meetings');
    Route::get('/meetings/{meeting}', [Site\MeetingController::class, 'show'])->name('meetings.show');

    /*
    | Unified file browser. /documents/* was the old Document Library and is
    | deep-linked from agendas, newsletters, and printed mailers, so those URLs
    | are kept forever as 301s onto their /files/* equivalent rather than
    | deleted.
    */
    Route::get('/files', [Site\FileController::class, 'index'])->name('files');
    Route::get('/files/{file}', [Site\FileController::class, 'show'])->name('files.show');
    Route::get('/files/{file}/download', [Site\FileController::class, 'download'])->name('files.download');

    Route::get('/documents', [Site\FileController::class, 'legacyIndex'])->name('documents');
    Route::get('/documents/{slug}', [Site\FileController::class, 'legacyShow'])->name('documents.show');
    Route::get('/documents/{slug}/download', [Site\FileController::class, 'legacyDownload'])->name('documents.download');

    // Report An Issue: open intake, no account required. The tracking link is
    // the credential — residents must be able to follow up without signing up.
    Route::get('/report-an-issue', [Site\ServiceRequestController::class, 'create'])->name('report');
    Route::post('/report-an-issue', [Site\ServiceRequestController::class, 'store'])
        ->middleware('throttle:10,60')->name('report.store');
    Route::get('/report-an-issue/submitted/{token}', [Site\ServiceRequestController::class, 'submitted'])->name('report.submitted');
    Route::get('/track', [Site\ServiceRequestController::class, 'trackForm'])->name('track');
    Route::post('/track', [Site\ServiceRequestController::class, 'track'])
        ->middleware('throttle:20,60')->name('track.lookup');
    Route::get('/track/{token}', [Site\ServiceRequestController::class, 'status'])->name('report.status');

    Route::get('/jobs', [Site\JobController::class, 'index'])->name('jobs');
    Route::get('/jobs/{jobPosting}', [Site\JobController::class, 'show'])->name('jobs.show');

    Route::get('/bids', [Site\BidController::class, 'index'])->name('bids');
    Route::get('/bids/{bid}', [Site\BidController::class, 'show'])->name('bids.show');

    Route::get('/forms/{formDefinition}', [Site\FormController::class, 'show'])->name('forms.show');
    Route::post('/forms/{formDefinition}', [Site\FormController::class, 'submit'])
        ->middleware('throttle:10,60')->name('forms.submit');

    Route::get('/search', [Site\SearchController::class, 'index'])->name('search');
    Route::get('/contact', [Site\ContactController::class, 'index'])->name('contact');
    Route::get('/accessibility', [Site\ContactController::class, 'accessibility'])->name('accessibility');

    // CMS pages last: an explicit route above always wins over a page slug.
    Route::get('/pages/{page}', [Site\PageController::class, 'show'])->name('page');
});

// Brand favicon, accent-tinted from DB-driven branding (public; pre-login).
Route::get('/brand/favicon', [FaviconController::class, 'svg'])->name('favicon.svg');
Route::get('/brand/favicon-png', [FaviconController::class, 'faviconPng'])->name('favicon.png');
Route::get('/brand/favicon-apple', [FaviconController::class, 'appleIcon'])->name('favicon.apple');

/*
|--------------------------------------------------------------------------
| Staff area
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    // First-run wizard. Step 1 (create admin) runs as a guest; EnsureSetup governs access.
    Route::prefix('setup')->group(function () {
        Route::get('/', [SetupController::class, 'index'])->name('setup.index');
        Route::post('/admin', [SetupController::class, 'storeAdmin'])->name('setup.admin');
        Route::post('/license', [SetupController::class, 'storeLicense'])->name('setup.license');
    });

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'show'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    Route::get('/magic/{user}', [AuthController::class, 'magic'])->name('magic-login')->middleware('signed');
    Route::get('/2fa', [AuthController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa', [AuthController::class, 'challengeVerify'])->middleware('throttle:10,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
});

Route::prefix('admin')->middleware(['auth', 'security.policy'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    /*
    | Content. Every resource below ships an index with massSelect bulk delete
    | (JSON ids[] -> bulk-destroy, behind a modal confirm: never a native
    | confirm() dialog).
    */
    $content = [
        'pages' => Admin\PageController::class,
        'news' => Admin\NewsController::class,
        'notices' => Admin\NoticeController::class,
        'events' => Admin\EventController::class,
        'departments' => Admin\DepartmentController::class,
        'staff' => Admin\StaffController::class,
        'officials' => Admin\OfficialController::class,
        'meetings' => Admin\MeetingController::class,
        'jobs' => Admin\JobPostingController::class,
        'bids' => Admin\BidController::class,
        'alerts' => Admin\AlertController::class,
        'forms' => Admin\FormController::class,
        'menus' => Admin\MenuItemController::class,
    ];
    foreach ($content as $uri => $controller) {
        Route::delete($uri . '/bulk', [$controller, 'bulkDestroy'])->name($uri . '.bulk-destroy');
        Route::resource($uri, $controller);
    }

    // Page builder: reorder/duplicate live outside the resource verbs.
    Route::post('pages/{page}/duplicate', [Admin\PageController::class, 'duplicate'])->name('pages.duplicate');
    Route::put('pages/{page}/publish', [Admin\PageController::class, 'publish'])->name('pages.publish');

    // Service requests: staff triage + status updates.
    Route::delete('service-requests/bulk', [Admin\ServiceRequestController::class, 'bulkDestroy'])->name('service-requests.bulk-destroy');
    Route::get('service-requests', [Admin\ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::get('service-requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::put('service-requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'update'])->name('service-requests.update');
    Route::post('service-requests/{serviceRequest}/updates', [Admin\ServiceRequestController::class, 'addUpdate'])->name('service-requests.updates.store');
    Route::delete('service-requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'destroy'])->name('service-requests.destroy');

    // Form submissions (inbox for the forms builder).
    Route::get('forms/{formDefinition}/submissions', [Admin\FormSubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::get('forms/{formDefinition}/submissions/export', [Admin\FormSubmissionController::class, 'export'])->name('forms.submissions.export');
    Route::delete('submissions/bulk', [Admin\FormSubmissionController::class, 'bulkDestroy'])->name('submissions.bulk-destroy');
    Route::get('submissions/{formSubmission}', [Admin\FormSubmissionController::class, 'show'])->name('submissions.show');
    Route::delete('submissions/{formSubmission}', [Admin\FormSubmissionController::class, 'destroy'])->name('submissions.destroy');

    /*
    | Unified File Manager. Replaces the three screens that came before it:
    | Document Library, Document Categories, and the flat Media Library.
    */
    Route::delete('files/bulk', [Admin\FileController::class, 'bulkDestroy'])->name('files.bulk-destroy');
    Route::post('files/bulk-move', [Admin\FileController::class, 'bulkMove'])->name('files.bulk-move');
    Route::get('files', [Admin\FileController::class, 'index'])->name('files.index');
    Route::post('files', [Admin\FileController::class, 'store'])->name('files.store');
    Route::get('files/{file}/edit', [Admin\FileController::class, 'edit'])->name('files.edit');
    Route::put('files/{file}', [Admin\FileController::class, 'update'])->name('files.update');
    Route::delete('files/{file}', [Admin\FileController::class, 'destroy'])->name('files.destroy');

    Route::post('folders', [Admin\FolderController::class, 'store'])->name('folders.store');
    Route::put('folders/{folder}', [Admin\FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [Admin\FolderController::class, 'destroy'])->name('folders.destroy');

    // Legacy admin URLs from the split systems, kept as redirects so staff
    // bookmarks and any lingering links land on the new manager.
    // Leading slash matters: without it the Location header is relative and a
    // browser resolves it against /admin/, landing on /admin/admin/files.
    Route::redirect('media', '/admin/files')->name('media.index');
    Route::redirect('documents', '/admin/files')->name('documents.index');
    Route::redirect('document-categories', '/admin/files')->name('document-categories.index');

    /*
    | Settings. Fleet-standard screens carried over from the scaffold, plus the
    | municipal-specific Site Identity screen.
    */
    Route::view('/settings', 'settings.index')->name('settings.index');
    Route::get('settings/site', [Admin\SiteSettingsController::class, 'edit'])->name('settings.site.edit');
    Route::put('settings/site', [Admin\SiteSettingsController::class, 'update'])->name('settings.site.update');

    Route::get('settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.general.edit');
    Route::put('settings/general', [GeneralSettingsController::class, 'update'])->name('settings.general.update');
    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding.edit');
    Route::put('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');
    Route::get('settings/notifications', [NotificationController::class, 'edit'])->name('settings.notifications.edit');
    Route::put('settings/notifications', [NotificationController::class, 'update'])->name('settings.notifications.update');
    Route::post('settings/notifications/test', [NotificationController::class, 'test'])->name('settings.notifications.test');
    Route::get('settings/integrations', [IntegrationController::class, 'edit'])->name('settings.integrations.edit');
    Route::put('settings/integrations', [IntegrationController::class, 'update'])->name('settings.integrations.update');
    Route::post('settings/integrations/test', [IntegrationController::class, 'test'])->name('settings.integrations.test');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/2fa', [TwoFactorController::class, 'show'])->name('settings.2fa.show');
    Route::post('settings/2fa/enable', [TwoFactorController::class, 'enable'])->name('settings.2fa.enable');
    Route::post('settings/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('settings.2fa.confirm');
    Route::delete('settings/2fa', [TwoFactorController::class, 'disable'])->name('settings.2fa.disable');
    Route::get('settings/tokens', [ApiTokenController::class, 'index'])->name('settings.tokens.index');
    Route::post('settings/tokens', [ApiTokenController::class, 'store'])->name('settings.tokens.store');
    Route::delete('settings/tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('settings.tokens.destroy');

    Route::get('settings/license', [LicenseController::class, 'edit'])->name('settings.license.edit');
    Route::put('settings/license', [LicenseController::class, 'update'])->name('settings.license.update');
    Route::post('settings/license/sync', [LicenseController::class, 'sync'])->name('settings.license.sync');
    Route::get('settings/updates', [UpdateController::class, 'show'])->name('settings.updates.show');
    Route::post('settings/updates/check', [UpdateController::class, 'check'])->name('settings.updates.check');
    Route::post('settings/updates/apply', [UpdateController::class, 'apply'])->name('settings.updates.apply');
    Route::post('settings/updates/auto', [UpdateController::class, 'toggleAuto'])->name('settings.updates.auto');
    Route::get('settings/backup', [BackupController::class, 'index'])->name('settings.backup.index');
    Route::get('settings/backup/config', [BackupController::class, 'downloadConfig'])->name('settings.backup.config');
    Route::get('settings/backup/database', [BackupController::class, 'downloadDatabase'])->name('settings.backup.database');
    Route::post('settings/backup/restore', [BackupController::class, 'restore'])->name('settings.backup.restore');

    Route::get('settings/host', [HostSslController::class, 'edit'])->name('settings.host.edit');
    Route::put('settings/host', [HostSslController::class, 'update'])->name('settings.host.update');
    Route::post('settings/host/letsencrypt', [HostSslController::class, 'letsencrypt'])->name('settings.host.letsencrypt');
    Route::post('settings/host/upload', [HostSslController::class, 'upload'])->name('settings.host.upload');
    Route::post('settings/host/self-signed', [HostSslController::class, 'selfSigned'])->name('settings.host.self-signed');

    Route::get('settings/firewall', [FirewallController::class, 'index'])->name('settings.firewall.index');
    Route::put('settings/firewall', [FirewallController::class, 'update'])->name('settings.firewall.update');
    Route::post('settings/firewall/bans', [FirewallController::class, 'ban'])->name('settings.firewall.ban');
    Route::delete('settings/firewall/bans/{bannedIp}', [FirewallController::class, 'unban'])->name('settings.firewall.unban');
    Route::delete('settings/firewall/sessions/{id}', [FirewallController::class, 'revokeSession'])->name('settings.firewall.session.revoke');
    Route::post('settings/firewall/sessions/bulk', [FirewallController::class, 'bulkSessions'])->name('settings.firewall.sessions.bulk');
    Route::post('settings/firewall/bulk', [FirewallController::class, 'bulk'])->name('settings.firewall.bulk');

    Route::get('settings/users', [UserController::class, 'index'])->name('settings.users.index');
    Route::get('settings/users/create', [UserController::class, 'create'])->name('settings.users.create');
    Route::post('settings/users', [UserController::class, 'store'])->name('settings.users.store');
    Route::get('settings/users/{user}/edit', [UserController::class, 'edit'])->name('settings.users.edit');
    Route::put('settings/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
    Route::delete('settings/users/{user}', [UserController::class, 'destroy'])->name('settings.users.destroy');
    Route::get('settings/audit', [AuditLogController::class, 'index'])->name('settings.audit.index');
    Route::delete('settings/audit/selected', [AuditLogController::class, 'destroySelected'])->name('settings.audit.destroy-selected');
    Route::delete('settings/audit/all', [AuditLogController::class, 'destroyAll'])->name('settings.audit.destroy-all');
});

/*
|--------------------------------------------------------------------------
| Constituents (resident CRM)
|--------------------------------------------------------------------------
| Staff-only, same gate as the rest of the panel. Kept in its own file so the
| feature owns its routing surface and cannot be half-registered.
*/
Route::prefix('admin')->middleware(['auth', 'security.policy'])->group(base_path('routes/constituents.php'));

/*
|--------------------------------------------------------------------------
| Jail And Arrest Records (optional module, ships DISABLED)
|--------------------------------------------------------------------------
| Loaded at top level because the module owns BOTH a public surface and a
| staff surface and declares its own prefixes and gates. Everything inside is
| behind EnsureRecordsModule except the settings screen that enables it.
*/
require base_path('routes/records.php');

/*
|--------------------------------------------------------------------------
| Pay Your Bill (optional module, ships DISABLED)
|--------------------------------------------------------------------------
| Loaded at top level because the module owns a public surface (the resident
| payment flow), an unauthenticated webhook endpoint, and a staff surface, and
| declares its own prefixes and gates. Everything inside is behind
| EnsurePaymentsEnabled except the settings screen that enables it.
*/
require base_path('routes/payments.php');
