<?php

use Illuminate\Support\Facades\Route;
use Larry\Larry\Controllers\ExchangeController;

Route::get('/exchanges/{id}', [ExchangeController::class, 'show']);


/* ----- Bare bones controller, mostly just to check the initial install ---- */

use Larry\Larry\Controllers\ChatController;
use Larry\Larry\Prompts\ChatPrompt;

class LarryPrompt extends ChatPrompt
{
    public function __construct()
    {
        $this->addSystemMessage("Your name is Larry.");
    }
}

class LarryController extends ChatController
{
    public function getPrompt(): ChatPrompt
    {
        return new LarryPrompt('chat');
    }
}

Route::post('/', LarryController::class);
