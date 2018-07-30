<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Company
 * @package DTApi\Models
 */
class Company extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
      'type_id',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inconvenienceSettings()
    {
        return $this->hasOne(InconvenienceSettings::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoices()
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function holidays()
    {
        return $this->hasMany(HolidaysToUser::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}
