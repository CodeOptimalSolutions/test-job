<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class InconvenienceInvoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'type_id',
        'physical_weekday_min',
        'physical_weekday_after',
        'phone_weekday_min',
        'phone_weekday_after',
        'physical_weekend_min',
        'physical_weekend_after',
        'phone_weekend_min',
        'phone_weekend_after',
        'physical_holiday_min',
        'physical_holiday_after',
        'phone_holiday_min',
        'phone_holiday_after',
    ];
}
