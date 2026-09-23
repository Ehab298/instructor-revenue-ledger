<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/subscribe', [SubscriptionController::class, 'create'])
    ->name('subscriptions.create');

Route::post('/subscriptions', [SubscriptionController::class, 'store'])
    ->name('subscriptions.store');
