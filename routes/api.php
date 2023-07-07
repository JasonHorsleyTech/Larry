<?php

use Illuminate\Support\Facades\Route;
use Larry\Larry\Controllers\ExchangeController;

Route::get('/exchanges/{id}', [ExchangeController::class, 'show']);
