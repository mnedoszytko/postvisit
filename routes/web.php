<?php

use App\Http\Controllers\MobileUploadController;
use Illuminate\Support\Facades\Route;

// Mobile upload (QR code bridge) — before SPA catch-all
Route::get('upload/{token}', [MobileUploadController::class, 'show']);
Route::post('upload/{token}', [MobileUploadController::class, 'store'])
    ->middleware('throttle:10,1');

// SPA catch-all: serve the Vue app for any non-API route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
