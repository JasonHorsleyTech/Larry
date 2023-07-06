<?php

namespace Larry\Larry\Controllers;

use App\Http\Controllers\Controller;
use Larry\Larry\Components\ChatComponent;
use Larry\Larry\Services\GptService;

abstract class ChatController extends Controller
{
    abstract public function getPrompt(): ChatComponent;

    final function __invoke(GptService $gptService)
    {
        // TODO: Move to validator
        $data = request()->validate([
            'user_transcripts' => 'array',
            'user_transcripts.*.said' => 'string',
            'user_transcripts.*.confidence' => 'numeric',
        ]);

        // TODO: Create exchange

        // TODO: Dispatch a job, return the exchange id.

        // TODO: larry default api route to long poll exchanges for updates.

    }
    /*
        // TODO: Job
        $gptResponse = $gptService->chatCompletion($this->getPrompt());

        // TODO: If response is function call and function is reprompt, loop

        return $gptResponse;
        */
}
