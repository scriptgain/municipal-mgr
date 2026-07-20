<?php

use App\Http\Controllers\Admin\ConstituentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Constituents (resident CRM)
|--------------------------------------------------------------------------
| Loaded from routes/web.php INSIDE the admin prefix + ['auth',
| 'security.policy'] group. Kept in its own file so the constituent feature
| owns its routing surface outright.
|
| Every route below is staff-only. There is deliberately no public counterpart
| anywhere in the app: this is resident PII on a government system, and the
| only way it can leak is if someone adds a route here without the gate.
*/

Route::delete('constituents/bulk', [ConstituentController::class, 'bulkDestroy'])->name('constituents.bulk-destroy');

Route::get('constituents', [ConstituentController::class, 'index'])->name('constituents.index');
Route::get('constituents/create', [ConstituentController::class, 'create'])->name('constituents.create');
Route::post('constituents', [ConstituentController::class, 'store'])->name('constituents.store');
Route::get('constituents/{constituent}', [ConstituentController::class, 'show'])->name('constituents.show');
Route::get('constituents/{constituent}/edit', [ConstituentController::class, 'edit'])->name('constituents.edit');
Route::put('constituents/{constituent}', [ConstituentController::class, 'update'])->name('constituents.update');
Route::delete('constituents/{constituent}', [ConstituentController::class, 'destroy'])->name('constituents.destroy');

Route::post('constituents/{constituent}/merge', [ConstituentController::class, 'merge'])->name('constituents.merge');

Route::post('constituents/{constituent}/interactions', [ConstituentController::class, 'storeInteraction'])->name('constituents.interactions.store');
Route::delete('constituents/{constituent}/interactions/{interaction}', [ConstituentController::class, 'destroyInteraction'])->name('constituents.interactions.destroy');
