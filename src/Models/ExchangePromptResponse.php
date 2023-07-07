<?php

namespace Larry\Larry\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangePromptResponse extends Model
{
    public $table = 'gpt_exchange_prompt_responses';

    protected $guarded = [];

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }

    public function promptResponse()
    {
        return $this->belongsTo(PromptResponse::class);
    }
}
