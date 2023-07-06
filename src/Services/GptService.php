<?php

namespace Larry\Larry\Services;

use Larry\Larry\Components\ChatComponent;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;

class GptService
{
    protected Client $client;

    public static $chatModelsThatCanCallFunctions = ['gpt-4-0613', 'gpt-4-32k-0613', 'gpt-3.5-turbo-0613', 'gpt-3.5-turbo-16k-0613'];

    public function __construct()
    {
        if (env('GPT_ENABLED', false) === false) {
            throw new \Exception('GPT is not enabled');
        }

        $this->client = OpenAI::client(env('GPT_OPENAI_API_KEY'));
    }

    public function chatCompletion(ChatComponent $chatPrompt)
    {
        $payload = [
            'model' => $chatPrompt->model,
            'messages' => $chatPrompt->messages,
        ];

        if ($chatPrompt->hasFunctions()) {
            // Hard to debug this :}
            if (in_array($payload['model'], self::$chatModelsThatCanCallFunctions) === false) {
                throw new \Exception('This model does not support calling functions');
            }

            $payload['functions'] = $chatPrompt->describeFunctionsToGpt();
            $payload['functionCall'] = $chatPrompt->forceGptFunctionalResponse();
        }

        // $promptResponse = PromptResponse::create([
        //     'prompt_name' => $chatPrompt::class,
        //     'type' => $chatPrompt->type,
        //     'payload' => $payload,
        // ]);

        try {
            $response = $this->client->chat()->create($payload);

            // $promptResponse->update([
            //     'response' => $response,
            //     'received_at' => now(),
            // ]);
        } catch (ErrorException $error) {
            // $promptResponse->update([
            //     'received_at' => now(),
            //     'error_message' => $error->getErrorMessage(),
            // ]);

            throw $error;
        }

        // return $promptResponse;
    }
}
