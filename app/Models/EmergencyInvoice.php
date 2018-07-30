<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyInvoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'charge_emergency',
        'physical_session',
        'phone_session',
    ];
}
