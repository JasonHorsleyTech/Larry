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

    // Generic
    public function addMessage(array $message): void
    {
        $this->messages[] = $message;
    }

    // System setup
    public function addSystemMessage(string $content): void
    {
        $this->addMessage([
            'role' => 'system',
            'content' => $content,
        ]);
    }

    // User said to GPT
    public function addUserMessage(string $content): void
    {
        $this->addMessage([
            'role' => 'user',
            'content' => $content,
        ]);
    }

    // GPT said to user
    public function addAssistantMessage(string $content): void
    {
        $this->addMessage([
            'role' => 'assistant',
            'content' => $content,
        ]);
    }

    // Function said to GPT
    public function addFunctionMessage(string $functionName, string $functionResult): void
    {
        $this->addMessage([
            'role' => 'function',
            'name' => $functionName,
            'content' => $functionResult,
        ]);
    }

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
