<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class UserLanguages extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_languages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'lang_id', 'type'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'lang_id');
    }

    public function userAllLanguage( $user_id )
    {
        return UserLanguages::where('user_id', '=', $user_id )->get();
    }
    public static function langExist($user_id, $lang_id){
        $result = UserLanguages::where('lang_id', $lang_id)->where('user_id', $user_id)->first();
        if( $result ){
            return $result->lang_id;
        } else {
            return 0;
        }
    }

    public static function deleteLang($user_id, $lang){
        UserLanguages::whereNotIn('lang_id', $lang)->where('user_id', $user_id)->delete();
    }
}
