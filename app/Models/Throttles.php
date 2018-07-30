<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Throttles extends Model
{
    protected $table = 'throttles';

    protected $fillable=[
        'id',
        'user_id',
        'ip',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
