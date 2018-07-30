<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{

    protected $fillable = [
        'export_list_id',
        'booking_id',
        'c_name',
        'c_id',
        'c_ref',
        'cost_place',
        'language',
        'status',
        'phone',
        'physic',
        'due',
        'created_date',
        'duration',
        'session_length',
        'rounded_session_length',
        'minimum_time',
        'after_minimum_time',
        'due_within24',
        'by_admin',
        'online',
        'fee',
        't_name',
        't_id',
        't_dob',
        'compensation',
        'withdrawn_late',
        'c_not_call',
        'inconvenience_time',
        'inconvenience_type',
        'customer_id',
        'travel_time',
        'travel_km'
    ];

    public function exportList()
    {
        return $this->belongsTo(ExportList::class);
    }
}
