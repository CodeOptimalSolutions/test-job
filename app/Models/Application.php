<?php

namespace DTApi\Models;

use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Application
 * @package DTApi\Models
 */
class Application extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return string
     */
    public function generateAuthToken()
    {
        $jwt = JWT::encode([
            'iss' => 'DTApi',
            'sub' => $this->key,
            'iat' => time(),
            'exp' => time() + (24 * 30 * 60 * 60),
        ], env('API_SECRET'));

        return $jwt;
    }
}
