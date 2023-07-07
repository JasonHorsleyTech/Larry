<?php

namespace Larry\Larry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserTranscript extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $table = 'gpt_user_transcripts';

    /*------------------------------------*\
                        SCOPES
     \*------------------------------------*/

    /*------------------------------------*\
                     RELATIONSHIPS
     \*------------------------------------*/
    public function transcriptable(): MorphTo
    {
        return $this->morphTo();
    }

    /*------------------------------------*\
                      ACCESSORS
     \*------------------------------------*/

    /*------------------------------------*\
                        MUTATORS
     \*------------------------------------*/

    /*------------------------------------*\
                         HELPERS
     \*------------------------------------*/
}
