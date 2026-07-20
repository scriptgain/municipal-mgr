<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\PaymentSettingsController;
use App\Http\Controllers\Payments\WebhookController;
use App\Http\Controllers\Site;
use App\Http\Middleware\EnsurePaymentsConfigured;
use App\Http\Middleware\EnsurePaymentsEnabled;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pay Your Bill
|--------------------------------------------------------------------------
| Loaded ONCE from routes/web.php at top level. The module declares its own
| prefixes and gates here rather than inheriting them, so the whole routing
| surface of the feature is visible in this one file and cannot end up half
| registered.
|
| SHIPS DISABLED. Everything except the settings screen sits behind
| EnsurePaymentsEnabled, which 404s unless an operator has switched the module
| on AND Stripe is configured. On a fresh install none of the URLs below
| resolve, and the only payments screen in the panel is the one that turns it
| on.
|
| Middleware is referenced by class rather than by alias so this file needs no
| edit to bootstrap/app.php to register.
*/

/*
|--------------------------------------------------------------------------
| Public: the resident payment flow
|--------------------------------------------------------------------------
| Outside every auth gate, like the rest of the public site. A resident paying
| a water bill does not hold an account and will not create one to do it.
*/
Route::middleware(EnsurePaymentsEnabled::class)->prefix('pay')->name('site.pay.')->group(function () {
    Route::get('/', [Site\PaymentController::class, 'index'])->name('index');

    // Bill lookup: the enumeration surface, so it is limited twice. The
    // controller's own per-IP counter (payments.lookup_rate_limit, 8/min) trips
    // first and explains itself; this route throttle is the harder backstop
    // that answers 429 to anything still hammering past that.
    Route::get('/lookup', [Site\PaymentController::class, 'lookupForm'])->name('lookup');
    Route::post('/lookup', [Site\PaymentController::class, 'lookup'])
        ->middleware('throttle:20,1')->name('lookup.submit');

    // Review and pay a looked-up bill. The bill is held in the SESSION, never
    // in the URL, so there is nothing here to enumerate.
    Route::get('/review', [Site\PaymentController::class, 'review'])->name('review');
    Route::post('/review', [Site\PaymentController::class, 'startBillPayment'])
        ->middleware('throttle:20,1')->name('start');

    // Pay without a bill reference (permit fees and similar).
    Route::get('/new/{type}', [Site\PaymentController::class, 'openForm'])->name('open');
    Route::post('/new/{type}', [Site\PaymentController::class, 'startOpenPayment'])
        ->middleware('throttle:20,1')->name('open.start');

    Route::get('/checkout', [Site\PaymentController::class, 'checkout'])->name('checkout');

    // Where Stripe returns the resident. The receipt token is the credential,
    // exactly as the tracking token is for a service request.
    Route::get('/complete/{token}', [Site\PaymentController::class, 'complete'])->name('complete');
    Route::get('/receipt/{token}', [Site\PaymentController::class, 'receipt'])->name('receipt');
    Route::get('/receipt/{token}/download', [Site\PaymentController::class, 'downloadReceipt'])->name('receipt.download');
});

/*
|--------------------------------------------------------------------------
| Stripe webhook
|--------------------------------------------------------------------------
| Gated on credentials being configured rather than on the enable switch: if an
| operator switches the module off with payments still in flight, those
| confirmations still have to settle against their bills, or residents end up
| charged with the bill still showing unpaid. Signature verification in the
| controller is the real security boundary.
|
| Exempt from CSRF in bootstrap/app.php: Stripe has no session and no token.
*/
Route::post('/stripe/webhook', WebhookController::class)
    ->middleware(EnsurePaymentsConfigured::class)
    ->name('payments.webhook');

/*
|--------------------------------------------------------------------------
| Staff area
|--------------------------------------------------------------------------
| Same gate as the rest of the panel, declared explicitly here.
*/
Route::prefix('admin')->middleware(['auth', 'security.policy'])->group(function () {

    /*
    | Bills, bill types and payments. All behind the module gate: while
    | payments are switched off there is nothing here for a signed-in staff
    | member to reach either.
    */
    Route::middleware(EnsurePaymentsEnabled::class)->group(function () {
        // Bills. Static paths are declared before /bills/{bill} so "import"
        // and "create" are never swallowed as a bill id.
        Route::delete('bills/bulk', [Admin\BillController::class, 'bulkDestroy'])->name('bills.bulk-destroy');
        Route::get('bills/import', [Admin\BillController::class, 'importForm'])->name('bills.import');
        Route::post('bills/import', [Admin\BillController::class, 'import'])->name('bills.import.store');
        Route::get('bills/create', [Admin\BillController::class, 'create'])->name('bills.create');
        Route::get('bills', [Admin\BillController::class, 'index'])->name('bills.index');
        Route::post('bills', [Admin\BillController::class, 'store'])->name('bills.store');
        Route::get('bills/{bill}', [Admin\BillController::class, 'show'])->name('bills.show');
        Route::get('bills/{bill}/edit', [Admin\BillController::class, 'edit'])->name('bills.edit');
        Route::put('bills/{bill}', [Admin\BillController::class, 'update'])->name('bills.update');
        Route::delete('bills/{bill}', [Admin\BillController::class, 'destroy'])->name('bills.destroy');

        // Counter actions: money taken at the desk or in the mail.
        Route::post('bills/{bill}/mark-paid', [Admin\BillController::class, 'markPaid'])->name('bills.mark-paid');
        Route::post('bills/{bill}/void', [Admin\BillController::class, 'void'])->name('bills.void');
        Route::post('bills/{bill}/reinstate', [Admin\BillController::class, 'reinstate'])->name('bills.reinstate');

        // Bill types.
        Route::delete('bill-types/bulk', [Admin\BillTypeController::class, 'bulkDestroy'])->name('bill-types.bulk-destroy');
        Route::resource('bill-types', Admin\BillTypeController::class)->except(['show']);

        // Payments received.
        Route::delete('payments/bulk', [Admin\PaymentAdminController::class, 'bulkDestroy'])->name('payments.bulk-destroy');
        Route::get('payments/reconciliation', [Admin\PaymentAdminController::class, 'reconciliation'])->name('payments.reconciliation');
        Route::get('payments/reconciliation/export', [Admin\PaymentAdminController::class, 'exportReconciliation'])->name('payments.reconciliation.export');
        Route::get('payments', [Admin\PaymentAdminController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [Admin\PaymentAdminController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/refund', [Admin\PaymentAdminController::class, 'refund'])->name('payments.refund');
    });

    /*
    | Settings. Deliberately OUTSIDE the module gate: this is the screen that
    | turns the module on, so it has to be reachable while it is off.
    */
    Route::get('settings/payments', [PaymentSettingsController::class, 'edit'])->name('settings.payments.edit');
    Route::put('settings/payments', [PaymentSettingsController::class, 'update'])->name('settings.payments.update');
    Route::post('settings/payments/toggle', [PaymentSettingsController::class, 'toggle'])->name('settings.payments.toggle');
    Route::post('settings/payments/connect', [PaymentSettingsController::class, 'connect'])->name('settings.payments.connect');
    Route::get('settings/payments/connect/return', [PaymentSettingsController::class, 'connectReturn'])->name('settings.payments.connect.return');
    Route::post('settings/payments/connect/refresh', [PaymentSettingsController::class, 'refreshConnect'])->name('settings.payments.connect.refresh');
    Route::post('settings/payments/connect/dashboard', [PaymentSettingsController::class, 'dashboardLink'])->name('settings.payments.connect.dashboard');
    Route::post('settings/payments/disconnect', [PaymentSettingsController::class, 'disconnect'])->name('settings.payments.disconnect');
});
