<?php

namespace Larry\Larry\Prompts;

use Larry\Larry\Components\ChatPrompt;
use Illuminate\Foundation\Http\FormRequest;

class AutoformPrompt extends ChatPrompt
{
    public FormRequest $formRequest;

    /**
     * @param FormRequest $formRequest
     * // TODO: @param bool $acceptPartial (GPT returns, even if required fields empty, or asks user followup)
     */
    public function __construct(
        FormRequest $formRequest,
    ) {
        $this->formRequest = $formRequest;

        $this->addSystemMessage("You fill out forms, based on user dictation (some voice-to-text may be garbled, do your best to infer). Form payloads must pass the given validation rules.");

        $this->addPlaceholderMessage('user');

        $this->addAssistantFunctionRequestMessage("getRules");

        $this->addFunctionResponseMessage("getRules", $this->$formRequest->rules());
    }
}
