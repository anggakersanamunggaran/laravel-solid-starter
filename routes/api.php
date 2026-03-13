<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (configured in bootstrap/app.php).
| Public routes require no token.
| Protected routes require a Sanctum Bearer token.
|
*/

// ── Public ────────────────────────────────────────────────────────────────
Route::prefix('users')->name('users.')->group(function () {
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/',  [UserController::class, 'index'])->name('index');
});
