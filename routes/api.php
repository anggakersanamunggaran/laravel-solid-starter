<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
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
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ── Protected (Sanctum token required) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users',  [UserController::class, 'index'])->name('users.index');
});
