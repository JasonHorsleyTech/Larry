<?php

namespace Larry\Larry\Controllers;

use App\Http\Controllers\Controller;
use Larry\Larry\Models\Conversation;
use Larry\Larry\Requests\CreateConversationRequest;
use Larry\Larry\Services\ConversationService;

class ConversationController extends Controller
{
    final public function create(CreateConversationRequest $request, ConversationService $conversationService)
    {
        $data = $request->validated();
        // TODO:
        // $this->middleware('auth');
        $userId = null;
        $conversation = Conversation::create([
            'prompt' => $data['prompt'],
            'user_id' => $userId
        ]);

        $exchange = $conversationService->initiateUserExchange($userId, $conversation, $data['transcripts']);

        return response()->json([
            'status' => 'gpt-initiated',
            'url' => route('api.larry.exchange.show', [
                'conversation_id' => $conversation->id,
                'exchange_id' => $exchange->id,
            ]),
        ]);
    }
}
