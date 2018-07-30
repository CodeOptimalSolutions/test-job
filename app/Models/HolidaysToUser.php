<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class HolidaysToUser extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'holiday_code'
    ];
}
