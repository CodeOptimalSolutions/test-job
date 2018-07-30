<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class UserTowns extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_towns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'town_id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function towns()
    {
        return $this->belongsTo(Town::class);
    }

    public function userAllTowns( $user_id )
    {
        return UserTowns::where('user_id', '=', $user_id )->get();
    }

    public static function townExist($user_id, $townId){

        $result = UserTowns::where('town_id', $townId)->where('user_id', $user_id)->first();
        if( $result ){
            return $result->town_id;
        } else {
            return 0;
        }

    }
}
