<?php

namespace Tests\Feature;

use A2Workspace\LaravelJwt\HasApiTokens;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('password');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    protected function getUserClass()
    {
        return User::class;
    }

    public function test_login()
    {
        $user = new User();
        $user->username = 'bk201';
        $user->password = $this->app->make(Hasher::class)->make('foobar123');
        $user->save();

        // =====================================================================
        // = 步驟1: 登入
        // =====================================================================

        $response = $this->post(
            '/api/auth/login',
            [
                'username' => $user->username,
                'password' => 'foobar123',
            ]
        );

        $response->assertOk();

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArrayHasKey('token_type', $decodedResponse);
        $this->assertArrayHasKey('expires_in', $decodedResponse);
        $this->assertArrayHasKey('access_token', $decodedResponse);

        // =====================================================================
        // = 步驟2: 取回使用者資料
        // =====================================================================

        $response2 = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$decodedResponse['access_token']}",
        ])->get('/api/auth/user');

        $response2->assertOk();

        $decodedResponse2 = $response2->decodeResponseJson();

        $this->assertArrayHasKey('username', $decodedResponse2);
        $this->assertEquals($user->username, $decodedResponse2['username']);
    }

    public function test_invalid_login()
    {
        $response = $this->post(
            '/api/auth/login',
            [
                'username' => 'nobody',
                'password' => 'abc123',
            ]
        );

        $response->assertStatus(401);
    }

    public function test_invalid_get_user()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/auth/user');

        $response->assertStatus(401);

        $response2 = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer INVALID_ACCESS_TOKEN",
        ])->get('/api/auth/user');

        $response2->assertStatus(401);
    }

    public function test_refresh()
    {
        $user = new User();
        $user->username = 'bk201';
        $user->password = $this->app->make(Hasher::class)->make('foobar123');
        $user->save();

        // =====================================================================
        // = 步驟1: 登入
        // =====================================================================

        $response = $this->post(
            '/api/auth/login',
            [
                'username' => $user->username,
                'password' => 'foobar123',
            ]
        );

        $response->assertOk();

        $decodedResponse = $response->decodeResponseJson();

        // =====================================================================
        // = 步驟2: 刷新 token
        // =====================================================================

        $response2 = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$decodedResponse['access_token']}",
        ])->post('/api/auth/refresh');

        $response2->assertOk();

        $decodedResponse2 = $response2->decodeResponseJson();

        $this->assertArrayHasKey('token_type', $decodedResponse2);
        $this->assertArrayHasKey('expires_in', $decodedResponse2);
        $this->assertArrayHasKey('access_token', $decodedResponse2);

        // =====================================================================
        // = 步驟3: 透過刷新的 token 取回使用者資料
        // =====================================================================

        $response3 = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$decodedResponse2['access_token']}",
        ])->get('/api/auth/user');

        $response3->assertOk();

        $decodedResponse3 = $response3->decodeResponseJson();

        $this->assertArrayHasKey('username', $decodedResponse3);
        $this->assertEquals($user->username, $decodedResponse3['username']);
    }

    public function test_invalid_refresh()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "Bearer INVALID_ACCESS_TOKEN",
        ])->post('/api/auth/refresh');

        $response->assertStatus(401);
    }
}

class User extends \Illuminate\Foundation\Auth\User
    implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use HasApiTokens;
}
