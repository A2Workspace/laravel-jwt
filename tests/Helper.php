<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use PHPOpenSourceSaver\JWTAuth\JWT;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Payload;

final class Helper
{
    public static function guard($guard = 'api'): JWTGuard
    {
        return Auth::guard($guard);
    }

    public static function getTokenInfo(string $token): array
    {
        $payload = static::decodeTokenPayload($token);

        $payload['__exp'] = Carbon::parse($payload['exp'])->format('Y-m-d H:i:s');
        $payload['__nbf'] = Carbon::parse($payload['nbf'])->format('Y-m-d H:i:s');
        $payload['__iat'] = Carbon::parse($payload['iat'])->format('Y-m-d H:i:s');
        $payload['__now'] = Carbon::now()->format('Y-m-d H:i:s');
        $payload['__expired'] = Carbon::parse($payload['exp']) < Carbon::now();

        return $payload;
    }

    public static function decodeTokenPayload(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token');
        }

        $base64Decoded = base64_decode($parts[1]);
        $payload = json_decode($base64Decoded, true);

        if (empty($payload)) {
            throw new InvalidArgumentException('Cannot decode payload');
        }

        return $payload;
    }

    public static function checkAndGetPayloadOrFail(string $token): Payload
    {
        $guard = static::guard();
        $guard->setToken($token);

        return $guard->getPayload();
    }

    public static function getBlacklist()
    {
        return static::guard()->blacklist();
    }
}
