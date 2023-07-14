<?php

namespace Larry\Larry\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Conversation extends Model
{
    public $table = 'gpt_conversations';

    public $guarded = [];

    /*------------------------------------*\
                     RELATIONSHIPS
     \*------------------------------------*/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(Exchange::class, 'conversation_id');
    }

    public function userTranscripts(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserTranscript::class,
            Exchange::class,
            'conversation_id',
            'transcriptable_id',
        )->where('transcriptable_type', Exchange::class);
    }

    public function promptResponses(): HasManyThrough
    {
        return $this->hasManyThrough(
            PromptResponse::class,
            Exchange::class,
            'conversation_id',
            'exchange_id',
        );
    }

    public function functionRequests(): HasManyThrough
    {
        return $this->hasManyThrough(
            FunctionRequest::class,
            Exchange::class,
            'conversation_id',
            'exchange_id',
        );
    }

    public function limitedUserTranscripts()
    {
        return $this->userTranscripts()->select(['gpt_user_transcripts.said', 'gpt_user_transcripts.created_at']);
    }

    public function limitedPromptResponses()
    {
        return $this->promptResponses()->select(['gpt_prompt_responses.response', 'gpt_prompt_responses.created_at']);
    }

    public function limitedFunctionRequests()
    {
        return $this->functionRequests()->select(['gpt_function_requests.request', 'gpt_function_requests.created_at']);
    }

    public function latestExchange(): HasOne
    {
        return $this->hasOne(Exchange::class, 'conversation_id')->latest();
    }
}
