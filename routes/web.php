<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Provide a named 'login' route to satisfy middleware redirectTo calls
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized - please login'], 401);
})->name('login');
