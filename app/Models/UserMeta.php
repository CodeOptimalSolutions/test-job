<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    protected $table = 'user_meta';

    protected $fillable = [
        'user_id',
        'customer_type',
        'consumer_type',
        'translator_type',
        'gender',
        'translator_level',
        'username',
        'post_code',
        'city',
        'country',
        'town',
        'worked_for',
        'organization_number',
        'not_get_notification',
        'not_get_emergency',
        'not_get_nighttime',
        'address',
        'address_2',
        'fee',
        'time_to_charge',
        'time_to_pay',
        'charge_km',
        'customer_id',
        'maximum_km',
        'additional_info',
        'reference',
        'cost_place',
    ];

    public function users(){
        return $this->belongsTo(User::class);
    }

}
