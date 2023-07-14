<?php

namespace Larry\Larry\Controllers;

use App\Http\Controllers\Controller;
use Larry\Larry\Models\Conversation;
use Larry\Larry\Models\Exchange;
use Larry\Larry\Requests\CreateConversationExchangeRequest;
use Larry\Larry\Services\ConversationService;

class ExchangeController extends Controller
{
    final public function create(int $conversation_id, CreateConversationExchangeRequest $request, ConversationService $conversationService)
    {
        $conversation = Conversation::findOrFail($conversation_id);
        // TODO:
        // $this->middleware('auth');
        // $userId = auth()->user()->id ?? null;
        $data = $request->validated();

        $exchange = $conversationService->initiateUserExchange(null, $conversation, $data['transcripts']);

        return response()->json([
            'status' => 'gpt-processing',
            'url' => route('api.larry.exchange.show', [
                'conversation_id' => $conversation->id,
                'exchange_id' => $exchange->id,
            ]),
        ]);
    }

    final public function show(int $conversation_id, int $exchange_id)
    {
        $conversation = Conversation::findOrFail($conversation_id);
        $exchange = Exchange::findOrFail($exchange_id);

        // TODO:
        // $this->middleware('auth');
        // $userId = auth()->user()->id ?? null;

        if (!$exchange->finished) {
            return response()->json([
                'status' => 'gpt-processing',
                'url' => route('api.larry.exchange.show', [
                    'conversation_id' => $conversation->id,
                    'exchange_id' => $exchange->id,
                ]),
            ]);
        }

        // TODO: If promptResponse is function call, and that function result should be spoken directly to the user, we should 'speak' => that

        $promptResponse = $exchange->promptResponses()->latest()->first();
        if (!$conversation->finished) {
            return response()->json([
                'status' => 'gpt-finished',
                'speak' => $promptResponse->content,
                'url' => route('api.larry.exchange.create', [
                    'conversation_id' => $conversation->id,
                ]),
            ]);
        }

        return response()->json([
            'status' => 'gpt-finished',
            'speak' => $promptResponse->content,
            'url' => route('api.larry.conversation.create', [
                'prompt' => $conversation->prompt,
            ]),
        ]);
    }
}
