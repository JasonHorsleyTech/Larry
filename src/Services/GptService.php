<?php

namespace Larry\Larry\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Larry\Larry\Models\PromptResponse;
use Larry\Larry\Prompts\BaseChatPrompt;
use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;

class GptService
{
    protected Client $client;

    private int $userId;

    public static $chatModelsThatCanCallFunctions = ['gpt-4-0613', 'gpt-4-32k-0613', 'gpt-3.5-turbo-0613', 'gpt-3.5-turbo-16k-0613'];

    public function __construct(int | null $userId = null)
    {
        if (Auth::check() === false && $userId === null) {
            // TODO: Warning
            // Warning: Running prompts without a userID can potentially flag your entire OpenAI entire account
        } else {
            $this->userId = $userId ?? Auth::user()->id;
        }

        if (env('GPT_ENABLED', false) === false) {
            throw new \Exception('GPT is not enabled');
        }

        $this->client = OpenAI::client(env('GPT_OPENAI_API_KEY'));
    }

    public function chatCompletion(BaseChatPrompt $baseChatPrompt, HasMany | null $createFromRelationship = null)
    {
        $payload = [
            'model' => $baseChatPrompt->model,
            'messages' => $baseChatPrompt->messages,
        ];
        // todo add userid to payload


        if ($baseChatPrompt->hasFunctions()) {
            // Hard to debug this :}
            if (in_array($payload['model'], self::$chatModelsThatCanCallFunctions) === false) {
                throw new \Exception('This model does not support calling functions');
            }

            $payload['functions'] = $baseChatPrompt->describeFunctions();
            $payload['function_call'] = $baseChatPrompt->getFunctionCall();
        }

        $promptResponsePayload = [
            'component' => $baseChatPrompt::class,
            'type' => $baseChatPrompt->type,
            'payload' => $payload,
        ];

        $promptResponse = ($createFromRelationship === null)
            ? PromptResponse::create($promptResponsePayload)
            : $createFromRelationship->create($promptResponsePayload);

        try {
            $gptResponse = $this->client->chat()->create($payload);

            $promptResponse->update([
                'response' => $gptResponse,
                'received_at' => now(),
            ]);
        } catch (ErrorException $error) {
            $promptResponse->update([
                'received_at' => now(),
                'error_message' => $error->getErrorMessage(),
            ]);

            throw $error;
        }

        return $promptResponse;
    }
}
