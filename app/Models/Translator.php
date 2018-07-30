<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Translator
 * @package DTApi\Models
 */
class Translator extends Model
{
    /**
     * @var string
     */
    protected $table = 'translator_job_rel';

    protected $dates = ['cancel_at'];

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'job_id',
        'created_at',
        'updated_at',
        'cancel_at',
        'completed_at',
        'completed_by',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobs()
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
