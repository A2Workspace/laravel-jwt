<?php

namespace A2Workspace\LaravelJwt;

use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Routing\Registrar as Router;

class LaravelJwt
{
    /**
     * Binds the routes.
     *
     * @param  array  $options
     * @return void
     */
    public static function routes(array $options = [])
    {
        $defaultOptions = [
            'prefix' => '/api/auth',
            'namespace' => '\A2Workspace\LaravelJwt\Controllers',
            'as' => 'jwt.',
        ];

        $options = array_merge($defaultOptions, $options);

        Route::group($options, function ($router) {
            $router->post('/login', 'AuthController@login')->name('login');
            $router->post('/logout', 'AuthController@logout')->name('logout');
            $router->post('/refresh', 'AuthController@refresh')->name('refresh');
            $router->get('/user', 'AuthController@me')->name('user');
        });
    }
}
