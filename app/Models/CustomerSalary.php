<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSalary extends Model
{
    protected $fillable = [
        'user_id',
        'physical_layman',
        'phone_layman',
        'physical_certified',
        'phone_certified',
        'physical_specialised',
        'phone_specialised',
        'travel_time_layman',
        'travel_time_certified',
        'travel_time_specialised',
        'km_price',
        'inconvenient_layman',
        'inconvenient_certified',
        'inconvenient_specialised',
        'transaction_layman',
        'transaction_certified',
        'transaction_specialised',
    ];
}
