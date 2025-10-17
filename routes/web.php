<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Add a simple login route for web requests (optional)
Route::get('/login', function () {
    return response()->json([
        'message' => 'Please use the API authentication endpoints',
        'endpoints' => [
            'POST /api/auth/register' => 'Register a new user',
            'POST /api/auth/login' => 'Login user'
        ]
    ]);
})->name('login');
