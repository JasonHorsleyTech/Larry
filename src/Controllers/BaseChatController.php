<?php

namespace Larry\Larry\Controllers;

use Larry\Larry\Prompts\BaseChatPrompt;
use App\Http\Controllers\Controller;
use Larry\Larry\Jobs\GptChatCompletion;
use Larry\Larry\Models\Conversation;
use Larry\Larry\Models\Exchange;
use Larry\Larry\Requests\UserTranscriptsRequest;

abstract class BaseChatController extends Controller
{
    abstract public function getPrompt(): BaseChatPrompt;

    // TODO: If post to /api/converse/, create a new *conversation* and exchange. Return both IDs.
    // If post to /api/converse/{conversation_id}/exchange, add to existing conversation and exchange.
    // Be sure to include previous exchanges as context
    public function __invoke(UserTranscriptsRequest $request)
    {
        $userId = auth()->user()->id ?? null;
        $data = $request->validated();

        $prompt = $this->getPrompt();

        // Create conversation
        $conversation = Conversation::create([
            'user_id' => $userId,
        ]);
        $exchange = $conversation->exchange()->create([]);

        $exchange->userTranscripts()->createMany($data['transcripts']);

        $exchange->userTranscripts()->get()->each(function ($transcript) use ($prompt) {
            $prompt->addUserTranscript($transcript);
        });

        GptChatCompletion::dispatchAfterResponse($userId, $prompt, $exchange);

        return response()->json([
            'type' => 'post',
            'url' => '/api/larry/conversations/' . $conversation->id,
        ]);
    }
}
