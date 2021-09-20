<?php

namespace A2Workspace\LaravelJwt;

use Tymon\JWTAuth\JWTGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

trait AuthenticatesUsers
{
    /**
     * 處理登入請求
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
     * 自 Request 取得驗證所需的欄位
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * 回傳認證守衛
     *
     * @return \Tymon\JWTAuth\JWTGuard
     */
    protected function guard(): JWTGuard
    {
        return Auth::guard('api');
    }

    /**
     * 取得驗證使用者名稱的欄位
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
     * @param  string $token
     *
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
     * 處理登出請求
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * 刷新 token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            return $this->respondWithToken($this->guard()->refresh());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid Access Token'], 401);
        }
    }

    /**
     * 回傳當前使用者資訊
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response 回傳使用者資訊
     */
    public function me(Request $request)
    {
        if ($this->guard()->check()) {
            return response()->json($this->guard()->user());
        } else {
            return response()->json(['error' => 'Invalid Access Token'], 401);
        }
    }
}
