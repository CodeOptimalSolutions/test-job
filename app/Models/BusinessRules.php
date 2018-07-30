<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessRules extends Model
{
    protected $fillable = [
        'invoice_id',
        'type_id',
        'physical_min',
        'physical_after',
        'phone_min',
        'phone_after',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
