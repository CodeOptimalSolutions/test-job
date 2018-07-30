<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'code',
        'date_from',
        'date_to',
    ];
}
