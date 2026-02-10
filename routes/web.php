<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all: serve the Vue app for any non-API route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
