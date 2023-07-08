<?php

namespace Larry\Larry\Controllers;

use Illuminate\Http\Request;
use Larry\Larry\Prompts\ChatPrompt;
use App\Http\Controllers\Controller;
use Larry\Larry\Jobs\GptChatCompletion;
use Larry\Larry\Models\Exchange;
use Larry\Larry\Requests\UserTranscriptsRequest;
use Larry\Larry\Services\GptService;

abstract class ChatController extends Controller
{
    abstract public function getPrompt(): ChatPrompt;

    final public function __invoke(UserTranscriptsRequest $request, GptService $gptService)
    {
        $userId = auth()->user()->id;
        $data = $request->validated();

        $prompt = $this->getPrompt();
        $exchange = Exchange::create([
            'user_id' => $userId,
        ]);

        $exchange->userTranscripts()->createMany($data['transcripts']);

        $exchange->userTranscripts()->get()->each(function ($transcript) use ($prompt) {
            $prompt->addUserTranscript($transcript);
        });

        GptChatCompletion::dispatchAfterResponse($userId, $prompt, $exchange);

        return response()->json([
            'type' => 'post',
            'exchange_id' => $exchange->id,
        ]);
    }
}
