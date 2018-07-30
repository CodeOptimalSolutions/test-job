<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoice
 * @package DTApi\Models
 */
class Invoice extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'company_id',
        'department_id',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function basicInvoices()
    {
        return $this->hasMany(BasicInvoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function businessRules()
    {
        return $this->hasOne(BusinessRules::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inconvenienceInvoices()
    {
        return $this->hasMany(InconvenienceInvoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function feeInvoices()
    {
        return $this->hasOne(FeeInvoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function emergencyInvoices()
    {
        return $this->hasOne(EmergencyInvoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function travelInvoices()
    {
        return $this->hasOne(TravelInvoice::class);
    }

}
