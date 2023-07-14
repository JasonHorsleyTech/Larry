<?php

namespace Larry\Larry\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larry\Larry\Rules\PromptExists;

class CreateConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return array_merge(
            ['prompt' => ['sometimes', new PromptExists]],
            (new CreateConversationExchangeRequest())->rules()
        );
    }
}
