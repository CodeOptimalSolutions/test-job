<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
