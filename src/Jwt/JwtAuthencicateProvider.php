<?php

namespace Zeaven\EasySuit\Jwt;

use Zeaven\EasySuit\Jwt\AutoRefreshJwtAuth;
// use Tymon\JWTAuth\Http\Middleware\Authenticate;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\ServiceProvider;

class JwtAuthencicateProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 指定路由开启 api 验证
        $this->app->when(['App\Http\Controllers\Api', 'App\Http\Controllers\Admin'])
            ->needs(Authenticate::class)
            ->give(function ($app) {
                return new AutoRefreshJwtAuth($app['auth']);
            });
    }
}
