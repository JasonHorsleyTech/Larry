<?php

namespace Larry\Larry\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Larry\Larry\Models\PromptResponse;
use Larry\Larry\Prompts\ChatPrompt;
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
        $this->userId = $userId === null ? Auth::user()->id : $userId;

        if (env('GPT_ENABLED', false) === false) {
            throw new \Exception('GPT is not enabled');
        }

        $this->client = OpenAI::client(env('GPT_OPENAI_API_KEY'));
    }

    public function chatCompletion(ChatPrompt $chatPrompt, HasMany | null $createFromRelationship = null)
    {
        $payload = [
            'model' => $chatPrompt->model,
            'messages' => $chatPrompt->messages,
        ];
        // todo add userid to payload

        if ($chatPrompt->hasFunctions()) {
            // Hard to debug this :}
            if (in_array($payload['model'], self::$chatModelsThatCanCallFunctions) === false) {
                throw new \Exception('This model does not support calling functions');
            }

            $payload['functions'] = $chatPrompt->describeFunctionsToGpt();
            $payload['functionCall'] = $chatPrompt->forceGptFunctionalResponse();
        }

        $promptResponsePayload = [
            'component' => $chatPrompt::class,
            'type' => $chatPrompt->type,
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
