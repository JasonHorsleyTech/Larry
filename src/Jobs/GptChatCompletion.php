<?php

namespace Larry\Larry\Jobs;

use App\OpenAI\Services\PromptService;
use App\OpenAI\Services\TranscriptionService;
use App\OpenAI\TranscriptionPiece;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Larry\Larry\Prompts\ChatPrompt;
use Larry\Larry\Models\Exchange;
use Larry\Larry\Models\ExchangePromptResponse;
use Larry\Larry\Services\GptService;

class GptChatCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $userUUID;
    public ChatPrompt $chatPrompt;
    public Exchange $exchange;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $userUUID,
        ChatPrompt $chatPrompt,
        Exchange $exchange,
    ) {
        $this->userUUID = $userUUID;
        $this->chatPrompt = $chatPrompt;
        $this->exchange = $exchange;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $service = new GptService($this->userUUID);
            $promptResponse = $service->chatCompletion($this->chatPrompt);

            ExchangePromptResponse::create([
                'exchange_id' => $this->exchange->id,
                'prompt_response_id' => $promptResponse->id,
            ]);

            if ($promptResponse->requiresBackendFunctionExecution($this->chatPrompt)) {
                $promptResponse->runFunctionAndUpdateChat($this->chatPrompt);
            }

            if (!$promptResponse->requiresBackendReprompt($this->chatPrompt)) {
                return;
            }

            $this->exchange->update([
                'prompts_required' => $this->exchange->prompts_required + 1,
            ]);

            GptChatCompletion::dispatch(
                $this->userUUID,
                $this->chatPrompt,
                $this->exchange,
            );
        });
    }
}