<?php

namespace DTApi\Models;

use Illuminate\Database\Eloquent\Model;

class UsersBlacklist extends Model
{
    protected $table = 'users_blacklist';

    protected $fillable = [
        'user_id',
        'translator_id',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function translatorExist($userId, $translatorId)
    {
        $result = UsersBlacklist::where('translator_id', $translatorId)->where('user_id', $userId)->first();
        if( $result ){
            return $result->translator_id;
        } else {
            return 0;
        }
    }

    public static function deleteFromBlacklist($userId, $translatorId)
    {
        UsersBlacklist::whereNotIn('translator_id', $translatorId)->where('user_id', $userId)->delete();
    }
}
