<?php

namespace Larry\Larry\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Larry\Larry\Prompts\BaseChatPrompt;

class PromptExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pass = class_exists($value) && is_subclass_of($value, BaseChatPrompt::class);
        if (!$pass) {
            $fail("The $attribute must exist and be a subtype of BaseChatPrompt.");
        }
    }
}
