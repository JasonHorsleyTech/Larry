<?php

use Illuminate\Support\Facades\Route;
use Larry\Larry\Controllers\ExchangeController;
use Larry\Larry\Controllers\ConversationController;
use Larry\Larry\Models\Conversation;
use Larry\Larry\Models\Exchange;

Route::post('/conversations', [ConversationController::class, 'create'])->name('conversation.create');
Route::post('/conversations/{conversation_id}/exchanges', [ExchangeController::class, 'create'])->name('exchange.create');
Route::get('/conversations/{conversation_id}/exchanges/{exchange_id}', [ExchangeController::class, 'show'])->name('exchange.show');


/* ----- Bare bones controller, mostly just to check the initial install ---- */

// use Larry\Larry\Controllers\BaseChatController;
// use Larry\Larry\Prompts\BaseChatPrompt;

// class LarryPrompt extends BaseChatPrompt
// {
//     public function __construct()
//     {
//         $this->addSystemMessage("You are Larry. No matter what the user asks, you respond with something like 'I am larry, you set it up right. This is a live GPT response'. Keep it short.");
//     }
// }

// class LarryController extends BaseChatController
// {
//     public function getPrompt(): BaseChatPrompt
//     {
//         return new LarryPrompt('chat');
//     }
// }

// Route::post('/', LarryController::class);
