<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Distance extends Model
{
    protected $table = 'distance';
    protected $fillable = ['user_id', 'by_user_id', 'job_id', 'distance', 'time'];
}
