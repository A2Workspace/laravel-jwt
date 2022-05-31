<?php

namespace Tests\Feature;

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

        if (($userClass = $this->getUserClass()) !== null) {
            $config->set('auth.providers.users.model', $userClass);
        }

        $config->set('auth.guards.api', ['driver' => 'jwt', 'provider' => 'users']);
    }

    /**
     * Get the Eloquent user model class name.
     *
     * @return string|null
     */
    protected function getUserClass()
    {
    }
}
