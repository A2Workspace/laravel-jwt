<?php

namespace A2Workspace\LaravelJwt;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait HasApiTokens
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Create a new token for current user.
     *
     * @return string
     */
    public function createToken(): string
    {
        return JWTAuth::fromUser($this);
    }
}
