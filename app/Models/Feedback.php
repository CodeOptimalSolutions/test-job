<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $fillable = ['user_id', 'by_user_id', 'job_id', 'rating', 'description'];

    public static function getJobIfRated($job, $user_id)
    {
        $query = $job->feedback;
        if (count($query)) {
            $filtered = $query->where('by_user_id', $user_id);
            if (count($filtered->all()))
                return $filtered->first()->id;
            else
                return 0;
        } else {
            return 0;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'by_user_id');
    }

}
