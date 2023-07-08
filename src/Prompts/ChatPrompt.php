<?php

namespace Larry\Larry\Prompts;

use Exception;
use Larry\Larry\ExposedFunctions\AbstractExposedFunction;
use Illuminate\Contracts\View\View as ViewInput;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Collection;
use Larry\Larry\Models\UserTranscript;

abstract class ChatPrompt
{
    public float $temperature = 0;
    public int $maxTokens = 1024;

    // @var chat|completion|edit
    public string $type = 'chat';

    // @var gpt-3.5-turbo|gpt-4|gpt-3.5-turbo-16k|gpt-4-32k
    public string $model = 'gpt-3.5-turbo-0613';

    public $messages = [];

    /**
     * Add message. If messages has a placeholder for matching role, put it there, otherwise push.
     *
     * Directly to OpenAI transporter based on this format
     * https://platform.openai.com/docs/api-reference/chat/create#chat/create-messages
     */
    private function addMessage(mixed $newMessage): self
    {
        $index = collect($this->messages)->firstWhere(fn ($message, $key) => $message['role'] === $newMessage['role'] && isset($message['placeholder']), false);

        if ($index !== false) {
            array_splice($this->messages, $index, 1, [$newMessage]);
        } else {
            $this->messages[] = $newMessage;
        }

        return $this;
    }

    /**
     * Render a Blade view to a string.
     * Allows all addMessages to take string or view('blade') input.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    private function parseMessageInput(ViewInput | ViewFactory | string $input): string
    {
        if ($input instanceof ViewInput || $input instanceof ViewFactory) {
            return $input->render();
        }

        if (is_string($input)) {
            return $input;
        }

        throw new Exception('Invalid input type');
    }

    public function addSystemMessage(mixed $input): self
    {
        return $this->addMessage([
            'role' => 'system',
            'content' => $this->parseMessageInput($input),
        ]);
    }

    public function addUserMessage(mixed $input): self
    {
        return $this->addMessage([
            'role' => 'user',
            'content' => $this->parseMessageInput($input),
        ]);
    }
    public function addUserTranscript(UserTranscript $transcript): self
    {
        return $this->addMessage([
            'role' => 'user',
            'content' => $transcript->said,
        ]);
    }

    public function addAssistantResponseMessage(mixed $input): self
    {
        return $this->addMessage([
            'role' => 'assistant',
            'content' => $this->parseMessageInput($input),
        ]);
    }

    public function addAssistantFunctionRequestMessage(string $functionName, string $functionArgsJson = ''): self
    {
        return $this->addMessage([
            'role' => 'assistant',
            'name' => $functionName,
            'content' => $functionArgsJson,
        ]);
    }

    /**
     * Function runs and responds to GPT.
     *
     * @param  string  $functionName
     * @param  mixed  $functionResult - must be json_encode-able
     */
    public function addFunctionResponseMessage(string $functionName, mixed $functionResult): self
    {
        return $this->addMessage([
            'role' => 'function',
            'name' => $functionName,
            'content' => json_encode($functionResult),
        ]);
    }


    /**
     * Push placeholder to messages[]. Next addMessage for matching role will go here.
     *
     * @param  string  $from user | assistant | function
     */
    public function addPlaceholderMessage(string $from): self
    {
        return $this->addMessage([
            'role' => $from,
            'placeholder' => true,
        ]);
    }


    // TODO: Rethink "hasFunctions" trait shit. Should just all be here. if has functions, then has functions.

    public function hasFunctions(): bool
    {
        return in_array(FunctionalPromptTrait::class, class_uses($this), true);
    }

    // Overwritten in FunctionalPromptTrait
    public function findFunction(): AbstractExposedFunction | false
    {
        return false;
    }

    // Overwritten in FunctionalPromptTrait
    public function describeFunctionsToGpt(): array
    {
        return [];
    }

    // Overwritten in FunctionalPromptTrait
    public function forceGptFunctionalResponse(): string
    {
        return 'none';
    }
}
