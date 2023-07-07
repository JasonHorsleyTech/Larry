<?php

namespace Larry\Larry\Components;

use Larry\Larry\ExposedFunctions\AbstractExposedFunction;

abstract class ChatComponent
{
    public float $temperature = 0;
    public int $maxTokens = 1024;

    // @var chat|completion|edit
    public string $type = 'chat';

    // @var gpt-3.5-turbo|gpt-4|gpt-3.5-turbo-16k|gpt-4-32k
    public string $model = 'gpt-3.5-turbo-0613';

    // @var array
    public array $messages = [];

    /**
     * Render a Blade view to a string.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    // TODO: All "add message" methods should take a string (static content)
    // Or an \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory

    // Generic
    public function addMessage(array $message): self
    {
        $this->messages[] = $message;

        return $this;
    }

    // System setup
    public function addSystemMessage(string $content): self
    {
        $this->addMessage([
            'role' => 'system',
            'content' => $content,
        ]);

        return $this;
    }

    // User said to GPT
    public function addUserMessage(string $content): self
    {
        $this->addMessage([
            'role' => 'user',
            'content' => $content,
        ]);

        return $this;
    }

    // GPT said to user
    public function addAssistantMessage(string $content): self
    {
        $this->addMessage([
            'role' => 'assistant',
            'content' => $content,
        ]);

        return $this;
    }

    // Function said to GPT
    public function addFunctionMessage(string $functionName, string $functionResult): self
    {
        $this->addMessage([
            'role' => 'function',
            'name' => $functionName,
            'content' => $functionResult,
        ]);

        return $this;
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
