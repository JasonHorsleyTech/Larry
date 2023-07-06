<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return 'Hello World';
})->name('test');

Route::prefix('/conversations')->group(function () {
    // Route::post('/', [ConversationController::class, 'store']);
    // Route::get('/{id}', [ConversationController::class, 'show']);
});
