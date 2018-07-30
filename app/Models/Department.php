<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'address_2',
        'city',
        'town',
        'country',
        'post_code',
        'organization_number',
        'cost_place',
        'additional_info',
        'fee',
        'charge_ob',
        'charge_km',
        'time_to_charge',
        'time_to_pay',
        'maximum_km',
        'reference_person',
        'payment_terms',
        'email_invoice',
        'paid',
        'after_min_time',
        'min_charging_time',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inconvenienceSettings()
    {
        return $this->hasOne(InconvenienceSettings::class);
    }

    public function holidays()
    {
        return $this->hasMany(HolidaysToUser::class);
    }

    public function invoices()
    {
        return $this->hasOne(Invoice::class);
    }
}
