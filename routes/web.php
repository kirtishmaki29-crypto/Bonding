<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Bonding Application
|--------------------------------------------------------------------------
*/

// Guest / Authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/auth/demo/{email}', [AuthController::class, 'demoLogin'])->name('auth.demo');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Chat application
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    // Chat API endpoints
    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/conversations', [ChatController::class, 'getConversations'])->name('conversations');
        Route::get('/users', [ChatController::class, 'getUsers'])->name('users');
        Route::post('/users/{user}/start', [ChatController::class, 'startConversation'])->name('users.start');
        Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages'])->name('messages.index');
        Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('messages.store');
        Route::post('/profile', [ChatController::class, 'updateProfile'])->name('profile.update');
    });
});
