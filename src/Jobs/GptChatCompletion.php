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
use Larry\Larry\Prompts\BaseChatPrompt;
use Larry\Larry\Models\Exchange;
use Larry\Larry\Models\ExchangePromptResponse;
use Larry\Larry\Models\FunctionRequest;
use Larry\Larry\Services\GptService;

class GptChatCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int | null $userId;
    public BaseChatPrompt $baseChatPrompt;
    public Exchange $exchange;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int | null $userId = null,
        BaseChatPrompt $baseChatPrompt,
        Exchange $exchange,
    ) {
        $this->userId = $userId;
        $this->baseChatPrompt = $baseChatPrompt;
        $this->exchange = $exchange;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('handling');
        /**
         * TODO: Unwrap. Wanted to avoid race condition of "exchange/show says we're done but we're not". Should re-look into that to
         * - Verify that might actually happen
         *
         * And if so, come up with a better solution. Because I'd rather have a partial database record entry trail for debug if anything goes wrong with function calling.
         *
         */

        $service = new GptService($this->userId);

        // Big wait here
        $promptResponse = $service->chatCompletion($this->baseChatPrompt, $this->exchange->promptResponses());

        if (!$promptResponse->calls_function) {
            // Assistant message will get picked up by next ping.
            return;
        }

        $requestedCall = $promptResponse->message['functionCall'] ?? false;

        if (!$requestedCall) return;

        $builder = $this->baseChatPrompt->findFunction($requestedCall['name']);

        // GPT asked us to run func we didn't expose???
        if (!$builder) return;

        $requestedArgumentString = $requestedCall['arguments'];
        $arguments = $builder::parseArgs($requestedArgumentString);

        // GPT asked we run this function with these args
        $this->baseChatPrompt->addAssistantFunctionRequestMessage(
            $builder::$name,
            $requestedArgumentString,
        );
        $functionModel = $this->exchange->functionRequests()->create([
            'name' => $builder::$name,
            'function' => $builder,
            'arguments' => $requestedArgumentString,
        ]);

        $instance = new $builder();
        $functionResponse = call_user_func_array([$instance, 'execute'], $arguments);

        $functionModel->update([
            'result' => $functionResponse,
        ]);

        // We do, add results to prompt messages
        $this->baseChatPrompt->addFunctionResponseMessage(
            $builder::$name,
            $functionResponse
        );

        if ($builder::$closesConversation) {
            $this->exchange->conversation->update(['finished' => true]);
        }

        if ($builder::$speaksResultToUser) {
            return;
        }

        $this->exchange->update([
            'prompts_required' => $this->exchange->prompts_required + 1,
        ]);

        GptChatCompletion::dispatch(
            $this->userId,
            $this->baseChatPrompt,
            $this->exchange,
        );
    }
}



// Potential issue: Function closes conversation, BUT results need GPT interpretation

/**
 * GPT could responds with a message to the user that *doesn't sound like a conversation closer*
 *
 * u: What should I do today? I'm in Austin.
 * a-f: get_forecast(loc) -- Function that *closes* conversation
 * f-a: 85f
 * a-u: it's sunny, do you like outdoor activities?
 *
 * // New conversation
 * u-a: yes
 * a-f: yes what?
 */

/**
 * GPT could also respond with another function call that implies *it doesn't think the conversation is finished*
 *
 * u: What should I do today? I'm in Austin.
 * a-f: get_forecast(loc) -- Function that *closes* conversation
 * f-a: 85f
 * a-f: get_activities(loc) -- Function that *does not* close conversation
 * f-a: hiking, swimming, eating
 * a-u: Weather is nice, how about hiking a trail?
 *
 * // New conversation
 * u-a: Sure, which one?
 * a-f: which what?
 */

// Issue: Devs can *say* their func closes the conversation, GPT could disagree.
// Solution:
// * tell devs to be super duper sure: Flakey

// * Followup all conversations by asking GPT if it thinks the conversation is over: Expensive (final query gets full context, doubles the price for all prompts with "conversation closing" functions.)

// * IF conversation is "closed" AND we're sending our final "tell user" GPT query, manipulate prompt such that
//   ^^ GPT cannot respond to user, can only call one of two functions: end_conversation_with($message) or continue_conversation_with($message);
//   ^^ finish_with sends $message to user no problem
//   ^^ continue_with re-opens the conversation

// Problems: Lots of tinkering to ensure GPT doesn't *over-assume* on "continue".
//           GPT could still respond with a message that sounds like a conversation closer, but isn't.

// Final solution: Something in the UI that allows users to *go back to a finished conversation* and pick back up.
// Might be the most air tight.


// Otherwise, we'll need at least one more prompt before we have something to sent to the user.
