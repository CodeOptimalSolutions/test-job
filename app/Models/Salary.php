<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Salary
 * @package DTApi\Models
 */
class Salary extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'standard_rate',
        'physical_session',
        'phone_session',
        'additional',
        'travel_time',
        'travel_km'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function basicSalaries()
    {
        return $this->hasMany(BasicSalaries::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inconvenienceSalaries()
    {
        return $this->hasMany(InconvenienceSalary::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function travelSalaries()
    {
        return $this->hasOne(TravelSalaries::class);
    }

}
