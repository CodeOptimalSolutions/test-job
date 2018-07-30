<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class ExportList extends Model
{

    protected $fillable = ['name', 'comment', 'query', 'payment_date', 'type'];

    public function exports()
    {
        return $this->hasMany(Export::class);
    }
}
