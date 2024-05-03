<?php

namespace A2Workspace\LaravelJwt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

trait AuthenticatesUsers
{
    /**
     * Handle login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $credentials = $this->credentials($request);

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \PHPOpenSourceSaver\JWTAuth\JWTGuard
     */
    protected function guard(): JWTGuard
    {
        return Auth::guard('api');
    }

    /**
     * Get the login username to be used by the controller.
     *
     *
     * @return string
     */
    protected function username()
    {
        return 'username';
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Handle logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Handle refresh token request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $newToken = $this->guard()->refresh();
            return $this->respondWithToken($newToken);
        } catch (JWTException $e) {
            return $this->respondWithInvalidAccess();
        }
    }

    /**
     * Handle reissue token request.
     *
     *@return \Illuminate\Http\JsonResponse
     */
    protected function reissue()
    {
        $jwtGuard = $this->guard();

        try {
            $temporaryToken = $jwtGuard->refresh();

            $jwtGuard->setToken($temporaryToken);
            $payload = $jwtGuard->getPayload();

            $newToken = $jwtGuard->tokenById($payload['sub']);

            return $this->respondWithToken($newToken);
        } catch (JWTException $e) {
            return $this->respondWithInvalidAccess();
        }
    }

    /**
     * @param  string|null  $message
     * @return \Illuminate\Http\JsonResponse
     */
    private function respondWithInvalidAccess(string $message = 'Invalid Access Token')
    {
        return response()->json(['error' => $message], 401);
    }

    /**
     * Handle get user info request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function me(Request $request)
    {
        if ($this->guard()->check()) {
            return response()->json($this->guard()->user());
        }

        return $this->respondWithInvalidAccess();
    }
}
