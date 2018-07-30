<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class TravelInvoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'km_reimbursement',
        'travel_time',
        'minimum_time_to_eligible',
        'maximum_km',
        'maximum_time',
        'per_km',
        'per_hour'
    ];
}
