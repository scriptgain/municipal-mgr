<?php

use App\Http\Controllers\Admin\ArrestRecordController;
use App\Http\Controllers\Admin\RecordsSettingsController;
use App\Http\Controllers\Site\RecordsController;
use App\Http\Middleware\EnsureRecordsModule;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Jail And Arrest Records
|--------------------------------------------------------------------------
| Loaded from routes/web.php at top level. The module owns its whole routing
| surface here so it cannot end up half-registered.
|
| The routes are ALWAYS registered; EnsureRecordsModule 404s them while the
| module is disabled. Registering them unconditionally means route() never
| throws for a nav composer or a view that asks about the module, while the
| middleware still makes the feature genuinely unreachable: a disabled
| install serves a plain not-found page, identical to any bad URL.
|
| The settings screen is the one exception: it stays reachable so an
| administrator has somewhere to turn the module on.
*/

/* ---------------------------------------------------------------- public */
Route::name('site.')->middleware(EnsureRecordsModule::class)->group(function () {
    Route::get('/arrest-records', [RecordsController::class, 'index'])->name('records.blotter');
    Route::get('/inmate-roster', [RecordsController::class, 'roster'])->name('records.roster');
    Route::get('/arrest-records/{ref}', [RecordsController::class, 'show'])->name('records.show');
});

/* ----------------------------------------------------------------- staff */
Route::prefix('admin')->middleware(['auth', 'security.policy'])->group(function () {

    // Settings are NOT behind the module gate: this is how it gets enabled.
    Route::get('settings/records', [RecordsSettingsController::class, 'edit'])->name('settings.records.edit');
    Route::put('settings/records', [RecordsSettingsController::class, 'update'])->name('settings.records.update');

    Route::middleware(EnsureRecordsModule::class)->group(function () {
        // Fixed segments first so none of them is ever swallowed by {key}.
        Route::delete('arrest-records/bulk', [ArrestRecordController::class, 'bulkDestroy'])->name('arrest-records.bulk-destroy');
        Route::get('arrest-records/expungements', [ArrestRecordController::class, 'expungements'])->name('arrest-records.expungements');
        Route::get('inmate-roster', [ArrestRecordController::class, 'roster'])->name('arrest-records.roster');

        Route::get('arrest-records', [ArrestRecordController::class, 'index'])->name('arrest-records.index');
        Route::get('arrest-records/create', [ArrestRecordController::class, 'create'])->name('arrest-records.create');
        Route::post('arrest-records', [ArrestRecordController::class, 'store'])->name('arrest-records.store');
        Route::get('arrest-records/{key}/edit', [ArrestRecordController::class, 'edit'])->name('arrest-records.edit');
        Route::put('arrest-records/{key}', [ArrestRecordController::class, 'update'])->name('arrest-records.update');
        Route::delete('arrest-records/{key}', [ArrestRecordController::class, 'destroy'])->name('arrest-records.destroy');

        // Publication state, kept off the resource verbs so the audit log can
        // tell "the clerk fixed a typo" apart from "the record went public".
        Route::put('arrest-records/{key}/publish', [ArrestRecordController::class, 'publish'])->name('arrest-records.publish');
        Route::put('arrest-records/{key}/unpublish', [ArrestRecordController::class, 'unpublish'])->name('arrest-records.unpublish');

        // Expungement. Distinct from destroy: destroys the mugshot too and
        // writes a compliance entry that outlives the record.
        Route::delete('arrest-records/{key}/expunge', [ArrestRecordController::class, 'expunge'])->name('arrest-records.expunge');
    });
});
