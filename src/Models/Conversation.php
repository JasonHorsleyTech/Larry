<?php

namespace JasonHorsleyTech\GptAssistant\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public $table = 'gpt_assistant_conversations';

    public $guarded = [];
}
