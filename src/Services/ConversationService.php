<?php

namespace Larry\Larry\Services;

use Larry\Larry\Jobs\GptChatCompletion;
use Larry\Larry\Models\Conversation;
use Larry\Larry\Models\Exchange;

class ConversationService
{
    public function initiateUserExchange(int | null $userId, Conversation $conversation, array $transcripts): Exchange
    {
        // Prompt loads initial messages (system->you are weatherbot, fake_assistant->get_user_loc, fake_function->78660, etc)
        $prompt = new $conversation->prompt;

        // Adds actual user transcript and prompt responses from prior exchanges, if any
        $this->addPreviousTranscriptsAndResponsesFromConversation($conversation, $prompt);

        // New thing user said, loaded last
        $exchange = $conversation->exchanges()->create([
            'prompts_required' => 1
        ]);
        $exchange->userTranscripts()->createMany($transcripts);
        $said = $exchange->userTranscripts()->pluck('said')->join(' ');
        $prompt->addUserMessage($said);

        // Sue me
        $testing = true;
        if ($testing) {
            $job = new GptChatCompletion($userId, $prompt, $exchange);
            $job->handle();
        } else {
            GptChatCompletion::dispatchAfterResponse($userId, $prompt, $exchange);
        }

        return $exchange;
    }

    private function addPreviousTranscriptsAndResponsesFromConversation(Conversation $conversation, $prompt)
    {
        $merged = collect();

        foreach ($conversation->userTranscripts as $transcript) {
            $merged->push([
                'from' => 'userTranscripts',
                'said' => $transcript->said,
                'created_at' => $transcript->created_at
            ]);
        }

        foreach ($conversation->promptResponses as $promptResponse) {
            if ($content = $promptResponse->content) {
                $merged->push([
                    'from' => 'promptResponse',
                    'said' => $content,
                    'created_at' => $promptResponse->created_at
                ]);
            }
        }

        foreach ($conversation->functionRequests as $functionRequest) {
            if ($result = $functionRequest->result) {
                $merged->push([
                    'from' => 'functionRequest',
                    'function_name' => $functionRequest->name,
                    'function_result' => $functionRequest->result,
                    'created_at' => $functionRequest->created_at
                ]);
            }
        }

        $sorted = $merged->sortBy('created_at');

        $result = $sorted->map(function ($item) use ($prompt) {
            if ($item['from'] === 'userTranscripts') {
                $prompt->addUserMessage($item['said']);
            }
            if ($item['from'] === 'promptResponse') {
                $prompt->addAssistantMessage($item['said']);
            }
            if ($item['from'] === 'functionRequest') {
                $prompt->addFunctionResponseMessage($item['function_name'], $item['function_result']);
            }
        });

        return $result->values()->all();
    }
}
