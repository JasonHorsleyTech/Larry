<?php

namespace Larry\Larry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PromptResponse extends Model
{
    public $table = 'gpt_prompt_responses';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'received_at' => 'datetime',
    ];

    /*------------------------------------*\
                     RELATIONSHIPS
     \*------------------------------------*/
    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class, 'exchange_id');
    }

    /*------------------------------------*\
                      ACCESSORS
     \*------------------------------------*/
    public function getFinishedAttribute(): bool
    {
        if (!is_null($this->received_at) && !is_null($this->response)) {
            return true;
        }

        return false;
    }

    public function getErroredAttribute(): bool
    {
        if (!is_null($this->received_at) && is_null($this->response)) {
            return true;
        }

        return false;
    }

    public function getSpeedAttribute(): int | false
    {
        if ($this->finished) {
            return $this->received_at->diffInMilliseconds($this->created_at);
        }

        return false;
    }


    public function getSpeedReadableAttribute(): string | false
    {
        if ($this->speed) {
            return $this->speed >= 1000 ? round($this->speed / 1000, 2) . 's' : $this->speed . 'ms';
        }

        return false;
    }

    public function getMessageAttribute(): array | false
    {
        return $this->response['choices'][0]['message'] ?? false;
    }

    public function getContentAttribute(): string | false
    {
        return $this->message['content'] ?? false;
    }

    public function getMessagesAttribute(): Collection
    {
        return collect($this->payload['messages']);
    }

    public function getModelAttribute(): string
    {
        return $this->payload['model'];
    }

    public function getInputTokenCountAttribute(): int
    {
        if ($this->response) {
            return intval($this->response['usage']['promptTokens']);
        }

        // Estimate
        return intval(strlen($this->messages->map(function ($message) {
            return $message['content'];
        })->implode(' ')) / 4);
    }

    public function getOutputTokenCountAttribute(): int
    {
        if ($this->response) {
            return intval($this->response['usage']['completionTokens']);
        }

        return 0;
    }

    /**
     *
     * @param array $messages
     * @param string $model
     */
    public function getInputCostAttribute(): int
    {
        $pricePerInputToken = [
            'gpt-4' => 0.03,
            'gpt-4-0613' => 0.03,
            'gpt-4-32k' => 0.06,
            'gpt-4-32k-0613' => 0.06,
            'gpt-3.5-turbo' => 0.0015,
            'gpt-3.5-turbo-0613' => 0.0015,
            'gpt-3.5-turbo-16k' => 0.003,
            'gpt-3.5-turbo-16k-0613' => 0.003,
        ];

        $cost = ($this->input_token_count / 1000) * $pricePerInputToken[$this->model];

        return intval($cost * 100) / 100;
    }

    public function getInputCostReadableAttribute(): string
    {
        $cents = $this->input_cost;
        return $cents === 0 ? '>0.1c' : $cents . "c";
    }

    /**
     *
     * @param array $messages
     * @param string $model
     */
    public function getOutputCostAttribute(): int
    {
        $pricePerOutputToken = [
            'gpt-4' => 0.06,
            'gpt-4-0613' => 0.06,
            'gpt-4-32k' => 0.12,
            'gpt-4-32k-0613' => 0.12,
            'gpt-3.5-turbo' => 0.002,
            'gpt-3.5-turbo-0613' => 0.002,
            'gpt-3.5-turbo-16k' => 0.004,
            'gpt-3.5-turbo-16k-0613' => 0.004,
        ];

        $cost = ($this->output_token_count / 1000) * $pricePerOutputToken[$this->model];

        return intval($cost * 100) / 100;
    }

    public function getOutputCostReadableAttribute(): string
    {
        $cents = $this->output_cost;
        return $cents === 0 ? '>0.1c' : $cents . "c";
    }

    public function getCostAttribute(): int
    {
        return $this->input_cost + $this->output_cost;
    }

    public function getCostReadableAttribute(): string
    {
        $cents = $this->cost;
        return $cents === 0 ? '>0.1c' : $cents . "c";
    }

    public function getCallsFunctionAttribute(): bool
    {
        return isset($this->message['functionCall']);
    }
}
