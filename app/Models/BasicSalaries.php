<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class BasicSalaries extends Model
{
    protected $fillable = [
        'salary_id',
        'type_id',
        'physical_min',
        'physical_after',
        'phone_min',
        'phone_after',
    ];

    public function salary()
    {
        return $this->belongsTo(Salary::class);
    }
}
