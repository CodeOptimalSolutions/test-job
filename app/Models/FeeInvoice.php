<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'charge_fee',
        'booking_over_phone',
        'booking_online',
    ];
}
