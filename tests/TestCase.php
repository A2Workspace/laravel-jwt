<?php

namespace Tests;

use A2Workspace\LaravelJwt\LaravelJwt;
use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LaravelJwt::routes();

        $this->artisan('jwt:secret');
    }

    protected function getPackageProviders($app)
    {
        return [LaravelServiceProvider::class];
    }

    /**
     * See: https://github.com/laravel/passport/blob/10.x/tests/Feature/PassportTestCase.php#L33
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make(Repository::class);

        $config->set('auth.defaults.provider', 'users');
        $config->set('auth.providers.users.model', $this->getUserClass());
        $config->set('auth.guards.api', ['driver' => 'jwt', 'provider' => 'users']);

        $config->set('jwt.ttl', 60); // 1 hour
        $config->set('jwt.refresh_ttl', 24 * 60); // 1 day
    }

    /**
     * Get the Eloquent user model class name.
     *
     * @return string|null
     */
    protected function getUserClass()
    {
        throw new \RuntimeException('User class not defined.');
    }
}
