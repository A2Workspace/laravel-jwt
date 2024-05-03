<?php

namespace Tests;

use A2Workspace\LaravelJwt\HasApiTokens;
use Carbon\Carbon;
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

    /**
     * {@inheritDoc}
     */
    protected function getUserClass()
    {
        return User::class;
    }

    private function createUser($username, $password): User
    {
        $user = new User;
        $user->username = $username;
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->save();

        return $user;
    }

    private function resetAuth()
    {
        app('auth')->forgetGuards();
        app()->instance('tymon.jwt', null);
    }

    // =========================================================================
    // = Specs
    // =========================================================================

    final public function test_login()
    {
        $this->createUser('new_user', 'pw123456');

        $loginResponse = $this->post(
            '/api/auth/login',
            [
                'username' => 'new_user',
                'password' => 'pw123456',
            ]
        );

        $loginResponse->assertOk();

        $decodedResponse = $loginResponse->decodeResponseJson();

        $this->assertArrayHasKey('token_type', $decodedResponse);
        $this->assertArrayHasKey('expires_in', $decodedResponse);
        $this->assertArrayHasKey('access_token', $decodedResponse);

        $this->resetAuth();
        $this->assertGuest('api');

        $accessToken = $decodedResponse['access_token'];
        $resourceResponse = $this->getJson('/api/auth/user', [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $resourceResponse->assertOk();

        $decodedResourceResponse = $resourceResponse->decodeResponseJson();

        $this->assertArrayHasKey('username', $decodedResourceResponse);
        $this->assertEquals('new_user', $decodedResourceResponse['username']);
    }

    final public function test_login_with_nonexistent_user()
    {
        $response = $this->post(
            '/api/auth/login',
            [
                'username' => 'NON_EXISTENT',
                'password' => 'NON_EXISTENT',
            ]
        );

        $response->assertStatus(401);
    }

    final public function test_get_user_with_invalid_token()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);

        $response2 = $this->getJson('/api/auth/user', [
            'Authorization' => "Bearer INVALID_ACCESS_TOKEN"
        ]);

        $response2->assertStatus(401);
    }

    final public function test_access_token_expired()
    {
        $this->createUser('new_user', 'pw123456');

        $loginResponse = $this->post(
            '/api/auth/login',
            [
                'username' => 'new_user',
                'password' => 'pw123456',
            ]
        );

        $loginResponse->assertOk();

        $this->resetAuth();
        $this->assertGuest('api');

        Carbon::setTestNow(now()->addHours(1));

        $accessToken = $loginResponse->decodeResponseJson()['access_token'];

        $response = $this->getJson('/api/auth/user', [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $response->assertStatus(401);
    }

    final public function test_refresh()
    {
        $this->createUser('new_user', 'pw123456');

        $response = $this->post(
            '/api/auth/login',
            [
                'username' => 'new_user',
                'password' => 'pw123456',
            ]
        );

        $accessToken = $response->decodeResponseJson()['access_token'];

        $refreshResponse = $this->post('/api/auth/refresh', [], [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $decodedRefreshResponse = $refreshResponse->decodeResponseJson();

        $this->assertArrayHasKey('token_type', $decodedRefreshResponse);
        $this->assertArrayHasKey('expires_in', $decodedRefreshResponse);
        $this->assertArrayHasKey('access_token', $decodedRefreshResponse);

        $resourceResponse = $this->getJson('/api/auth/user', [
            'Authorization' => "Bearer {$decodedRefreshResponse['access_token']}",
        ]);

        $resourceResponse->assertOk();

        $decodedResourceResponse = $resourceResponse->decodeResponseJson();
        $this->assertEquals('new_user', $decodedResourceResponse['username']);
    }

    final public function test_invalid_refresh()
    {
        $response = $this->post('/api/auth/refresh', [], [
            'Authorization' => "Bearer INVALID_ACCESS_TOKEN",
        ]);

        $response->assertStatus(401);
    }

    final public function test_refresh_with_expired_token()
    {
        $this->createUser('new_user', 'pw123456');

        $loginResponse = $this->post(
            '/api/auth/login',
            [
                'username' => 'new_user',
                'password' => 'pw123456',
            ]
        );

        $loginResponse->assertOk();

        $this->resetAuth();
        $this->assertGuest('api');

        Carbon::setTestNow(now()->addHours(1));

        $accessToken = $loginResponse->decodeResponseJson()['access_token'];

        $refreshResponse = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $refreshResponse->assertOk();

        $this->resetAuth();
        // $this->assertGuest('api');

        $decodedResponse = $refreshResponse->decodeResponseJson();

        $this->assertArrayHasKey('token_type', $decodedResponse);
        $this->assertArrayHasKey('expires_in', $decodedResponse);
        $this->assertArrayHasKey('access_token', $decodedResponse);

        $refreshedAccessToken = $decodedResponse['access_token'];

        $resourceResponse = $this->getJson('/api/auth/user', [
            'Authorization' => "Bearer {$refreshedAccessToken}",
        ]);

        $resourceResponse->assertOk();

        $decodedResourceResponse = $resourceResponse->decodeResponseJson();
        $this->assertArrayHasKey('username', $decodedResourceResponse);
        $this->assertEquals('new_user', $decodedResourceResponse['username']);
    }
}

class User extends \Illuminate\Foundation\Auth\User
implements \PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject
{
    use HasApiTokens;
}
