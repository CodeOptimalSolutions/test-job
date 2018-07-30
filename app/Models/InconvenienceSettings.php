<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class InconvenienceSettings extends Model
{
    protected $fillable = [
        'company_id',
        'department_id',
        'user_id',
        'weekends_day_before_after',
        'holiday_day_before_after',
        'inconvenience_standard_rate',
        'standard_rate',
        'weekdays_start',
        'weekdays_end',
        'weekend_start',
        'weekend_end',
        'holiday_start',
        'holiday_end',
    ];
}
