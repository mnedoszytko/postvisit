<?php

use App\Http\Controllers\MobileUploadController;
use Illuminate\Support\Facades\Route;

// Mobile upload (QR code bridge) — before SPA catch-all
Route::get('upload/{token}', [MobileUploadController::class, 'show']);
Route::post('upload/{token}', [MobileUploadController::class, 'store'])
    ->middleware('throttle:10,1');

// Version info (public, no auth)
Route::get('version', function () {
    $commit = trim((string) shell_exec('git rev-parse --short HEAD 2>/dev/null'));
    $message = trim((string) shell_exec('git log -1 --format=%s 2>/dev/null'));
    $deployedAt = trim((string) shell_exec('git log -1 --format=%ci 2>/dev/null'));

    return response()->json([
        'commit' => $commit,
        'message' => $message,
        'deployed_at' => $deployedAt,
        'server_time' => now()->toIso8601String(),
    ]);
});

// SPA catch-all: serve the Vue app for any non-API route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
