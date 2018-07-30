<?php

namespace DTApi\Models;

use Firebase\JWT\JWT;
use DTApi\Traits\HasRoleAndPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class User
 * @package DTApi\Models
 */
class User extends Authenticatable
{

    use HasRoleAndPermission;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'mobile', 'company_id', 'department_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @param Application $app
     * @return string
     */
    public function generateAuthToken(Application $app)
    {
        $jwt = JWT::encode([
            'iss' => $app->key,
            'sub' => $this->email,
            'iat' => time(),
            'jti' => sha1($app->key . $this->email . time()),
        ], env('API_SECRET'));

        return $jwt;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userMeta()
    {
        return $this->hasOne(UserMeta::class);
    }

    /**
     * @return mixed
     */
    public function languages()
    {
        return $this->hasMany(UserLanguages::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customerSalary()
    {
        return $this->hasOne(CustomerSalary::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inconvenienceSettings()
    {
        return $this->hasOne(InconvenienceSettings::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return Notification
     */
    public function newNotification()
    {
        $notification = new Notification;
        $notification->user()->associate($this);

        return $notification;
    }

    /**
     * @param $roleid
     * @return mixed
     */
    public static function getAllUserByRoleId($roleid)
    {

        $users = User::whereHas('roleUser', function ($q) use ($roleid) {
            $q->where('role_id', $roleid);
        })->get();
        return $users;

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function roleUser()
    {

        return $this->hasOne(RoleUser::class, 'user_id');

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function towns()
    {
        return $this->hasMany(UserTowns::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function average()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translator()
    {
        return $this->hasMany(Translator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usersBlacklist()
    {
        return $this->hasMany(UsersBlacklist::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoices()
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * @param $user_id
     * @return float|string
     */
    public static function getUserAverage($user_id)
    {

//        $avg = Cache::remember('average', 10, function() use ($user_id) {
//            return Feedback::select(DB::raw('AVG(rating) average'))
        $avg = Feedback::select(DB::raw('AVG(rating) average'))
            ->where('user_id', $user_id)
            ->groupBy('user_id')
            ->first();
//        });

        if ($avg) {
            $avg->average = intval($avg->average * 2) / 2;
            return $avg->average;
        } else {
            return '0';
        }

    }

    /**
     * @param $translator_type
     * @param $joblanguage
     * @param $gender
     * @param $translator_level
     * @return mixed
     */
    public static function getPotentialUsers($translator_type, $joblanguage, $gender, $translator_level, $translatorsId = null)
    {
        $users = User::where('user_type', 2)->whereNotIn('id', $translatorsId);
        
        $users->whereHas('languages', function ($query) use ($joblanguage) {
            $query->where('lang_id', '=', $joblanguage);
        })
            ->whereHas('userMeta', function ($q) use ($translator_level, $translator_type, $gender, $joblanguage) {
                $q->whereIn('translator_level', $translator_level)
                    ->where('translator_type', $translator_type);
                if (!is_null($gender)) $q->where('gender', $gender);

            });

        return $users->get();

    }

}
