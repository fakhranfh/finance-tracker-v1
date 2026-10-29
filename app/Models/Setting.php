<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * App-wide key/value setting (not tied to a user), e.g. the hashed terminal password.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    //
}
