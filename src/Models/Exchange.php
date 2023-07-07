<?php

namespace Larry\Larry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Exchange extends Model
{
    public $table = 'gpt_exchanges';

    public $guarded = [];

    /*------------------------------------*\
                     RELATIONSHIPS
     \*------------------------------------*/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userTranscripts(): MorphMany
    {
        return $this->morphMany(UserTranscript::class, 'transcriptable');
    }

    public function promptResponses(): HasManyThrough
    {
        return $this->hasManyThrough(PromptResponse::class, ExchangePromptResponse::class, 'exchange_id', 'id', 'id', 'prompt_response_id');
    }

    /*------------------------------------*\
                      ACCESSORS
     \*------------------------------------*/
    public function getFinishedAttribute(): bool
    {
        $promptsExecuted = $this->promptResponses()->whereNotNull('received_at')->count();

        return $promptsExecuted >= $this->prompts_required;
    }
}
