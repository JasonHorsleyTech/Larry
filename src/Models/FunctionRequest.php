<?php

namespace Larry\Larry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionRequest extends Model
{
    public $table = 'gpt_function_requests';
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
}
