<?php

namespace Larry\Larry\Prompts;

use Exception;
use Larry\Larry\ExposedFunctions\AbstractExposedFunction;
use Illuminate\Contracts\View\View as ViewInput;
use Illuminate\Contracts\View\Factory as ViewFactory;

abstract class BaseChatPrompt
{
    public float $temperature = 0;
    public int $maxTokens = 1024;

    // @var chat|completion|edit
    public string $type = 'chat';

    // @var gpt-3.5-turbo|gpt-4|gpt-3.5-turbo-16k|gpt-4-32k
    public string $model = 'gpt-3.5-turbo-0613';

    public $messages = [];

    // @var array[AbstractExposedFunction]
    public $functions = [];

    public $functionCall = null;

    /**
     * Add message.
     *
     * IDEA: Placeholder messages. Might produce better results if, say, the laravel validation rules are always the *last* message in the batch...
     * -- setup --
     *      $p->addSystemMessage('You generate payload to pass given laravel validation rules');
     *      $p->addPlaceholderMessage('User');
     *      $p->addFunctionResponseMessage("{first_name: ['required', 'string'], last_name: ['required', 'string']}");
     *
     * -- Job adds transcript, but before the rules --
     *      $p->addUserMessage("Hi, I'm Jason Horsley")
     *
     * TODO: See if this produces better results? Or if there's another use-case for it.
     *
     * IDEA: Instead of "placeholder", we could
     *  -- $p->addMessage('system', 'system message here');
     *  -- $p->addClosingMessage('function', {first_name: ['required']});
     *  -- $p->addMessage('user', 'Hi, Im Jason Horsley');
     *  -- $p->getMessages() returns array_merge($addMessages, $endMessages).
     *
     * Issue: Not as readable as placeholder, but a hell of a lot easier to code... If a conversation already has two exchanges, should both go in the "placeholder" section?
     *
     * Directly to OpenAI transporter based on this format
     * https://platform.openai.com/docs/api-reference/chat/create#chat/create-messages
     */
    private function addMessage(mixed $newMessage): self
    {
        $this->messages[] = $newMessage;
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

    public function addAssistantMessage(mixed $input): self
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
     * Expose function
     *
     * @param string $functionClass - must extend AbstractExposedFunction
     */
    public function exposeFunction(string $functionClass)
    {
        $this->functions[] = $functionClass;
    }

    /**
     * Expose function and force GPT to use it.
     *
     * @param string $functionClass - must extend AbstractExposedFunction
     */
    public function exposeForcedFunction(string $functionClass)
    {
        $this->functionCall = $functionClass;
        $this->exposeFunction($functionClass);
    }

    public function hasFunctions(): bool
    {
        return count($this->functions) > 0;
    }

    public function getFunctionCall(): string
    {
        if (!$this->hasFunctions()) {
            return "none";
        }

        if ($this->functionCall) {
            return json_encode(['name' => $this->functionCall]);
        }

        return "auto";
    }

    public function describeFunctions(): array
    {
        return collect($this->functions)->map(function ($function) {
            return $function::describe();
        })->toArray();
    }

    public function findFunction(string $functionName): string
    {
        return collect($this->functions)->first(function ($function) use ($functionName) {
            return $function::describe()['name'] === $functionName;
        });
    }
}
