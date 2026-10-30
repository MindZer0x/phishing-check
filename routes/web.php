<?php

use App\Http\Controllers\CheckController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::post('/check', CheckController::class)
    ->middleware('throttle:checks')
    ->name('check');
