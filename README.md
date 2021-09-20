# A2Workspace/Laravel-JWT

一個隨開即用的 API 認證服務。

此套件是基於 [tymon/jwt-auth](https://github.com/tymondesigns/jwt-auth) 的包裝，並提供一個簡易的 `AuthenticatesUsers` 特性方便擴充。

此套件相容於 **Nuxt** 的 `auth-nuxt` 模組。如何設定請參考 [# Nuxt 登入](#Nuxt-登入)


## 安裝
此套件尚未發布到 **Packagist** 需透過下列方法安裝：

```
composer config repositories.hooks vcs https://github.com/A2Workspace/laravel-jwt.git
composer require "a2workspace/laravel-jwt:*"
```

接著，你應該執行 `laravel-jwt:install` Artisan 指令來進行安裝。
該指令會生成設定檔與 JWT 密鑰。

```
php artisan laravel-jwt:install
```

現在應該會有個 `config/jwt.php` 檔案。


## 快速開始

要讓你的 API 可以透過 *jwt* 登入需要做以下的設定：


### 修改 User 資料模型

首先讓你的 `User` 模型實作 `Tymon\JWTAuth\Contracts\JWTSubject` 介面；
並將 `A2Workspace\LaravelJwt\HasApiTokens` 特性加到你的 `User` 模型中；

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use A2Workspace\LaravelJwt\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
}
```
有關 `Tymon\JWTAuth\Contracts\JWTSubject` 設定可參考 [https://jwt-auth.readthedocs.io/en/develop/quick-start/#update-your-user-model](https://jwt-auth.readthedocs.io/en/develop/quick-start/#update-your-user-model)


### 設定登入認證守衛 (Auth Guard)

接著，找到你專案的 `config/auth.php` 設定檔。將 `api` 的 `driver` 修改為 `jwt`。

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

### 註冊路由

最後，你應該在 `App\Providers\AuthServiceProvider` 的 `boot` 中註冊 `LaravelJwt::routes`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use A2Workspace\LaravelJwt\LaravelJwt;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if (! $this->app->routesAreCached()) {
            LaravelJwt::routes();
        }
    }
}
```

## 自訂使用者回傳值

你可以透過覆寫 `/api/auth/user` 路徑來修改回傳的使用者資訊:

```php
// routes/api.php

Route::middleware('auth:api')->get('/auth/user', function (Request $request) {
    return new UserResource($request->user());
});

```

## 自訂認證控制器

`A2Workspace\LaravelJwt\AuthenticatesUsers` 提供了 JWT 登入認證所需的所有方法，僅需要在控制器中使用該特性，並註冊到專案的路由檔案。

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use A2Workspace\LaravelJwt\AuthenticatesUsers;

class AuthController extends Controller
{
    use AuthenticatesUsers;
}
```

### 指定認證守衛

需對應 `configs/auth.php` 中的 `guards` 名稱，且 `driver` 必須為 `jwt`。

```php
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    use AuthenticatesUsers;

    /**
     * 回傳認證守衛
     *
     * @return \Tymon\JWTAuth\JWTGuard
     */
    protected function guard(): JWTGuard
    {
        return Auth::guard('custom-api-guard');
    }
}
```

### 指定登入時的帳號欄位

預設為 `username`，你可以自行修改為 `phone`、`email`、`account`...等。

```php
class AuthController extends Controller
{
    use AuthenticatesUsers;

    /**
     * 取得驗證使用者名稱的欄位
     *
     * @return string
     */
    protected function username()
    {
        return 'username';
    }
}
```

### 註冊路由

```php
// routes/api.php
Route::post('/auth/login', 'AuthController@login');
Route::post('/auth/logout', 'AuthController@logout');
Route::post('/auth/refresh', 'AuthController@refresh');
Route::get('/auth/user', 'AuthController@me');
```

*注意: 當使用自訂控制器時，就不需要在 `App\Providers\AuthServiceProvider` 中重複註冊 `LaravelJwt:routes` 了。*

## Nuxt 登入

此套件相容於 **Nuxt** 的 `auth-nuxt` 模組中的 `Laravel JWT` ([參考這裡](https://auth.nuxtjs.org/providers/laravel-jwt))。

```js
// nuxt.config.js 

auth: {
  strategies: {
    'laravelJWT': {
      provider: 'laravel/jwt',
      url: '<laravel url>',
      endpoints: {
        // ...預設這裡不需要修改
      },
      token: {
        property: 'access_token',
        maxAge: 60 * 60
      },
      refreshToken: {
        maxAge: 20160 * 60
      },
    },
  }
}
```

當使用 `UserResource` 時，由於使用者資料是放在 `data` 欄位而不是最頂層，需增加 `user.property` 的設定:

```js
// nuxt.config.js 

auth: {
  strategies: {
    'laravelJWT': {
      provider: 'laravel/jwt',

      // ...  

      user: {
        property: 'data'
      },

    },
  }
}
```
