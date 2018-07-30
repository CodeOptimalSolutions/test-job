<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class TravelSalaries extends Model
{
    protected $fillable = [
        'salary_id',
        'km_reimbursement',
        'travel_time',
        'minimum_time_to_eligible',
        'maximum_km',
        'per_km',
        'per_hour'
    ];
}
