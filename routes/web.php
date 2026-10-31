<?php

use App\Check\ExampleEmail;
use App\Http\Controllers\CheckController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home', ['exampleEmails' => ExampleEmail::forForm()])->name('home');

Route::post('/check', CheckController::class)
    ->middleware('throttle:checks')
    ->name('check');
