<?php

namespace Zeaven\EasySuit\SanctumExtension;

use App\Http\Middleware\Authenticate;
use Event;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Zeaven\EasySuit\SanctumExtension\CacheGuard;
use Zeaven\EasySuit\SanctumExtension\Listeners\TokenAuthenticatedListener;
use Zeaven\EasySuit\SanctumExtension\Middleware\TokenRefreshAuthenticate;

class SanctumExtensionProvider extends ServiceProvider
{


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 指定路由开启 api 验证
        // $this->app->when(['App\Http\Controllers\Api', 'App\Http\Controllers\Admin'])
        //     ->needs(Authenticate::class)
        //     ->give(function ($app) {
        //         return new TokenRefreshAuthenticate($app['auth']);
        //     });
    }

    /**
     * Bootstrap any application services.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function boot()
    {
        if (config('easy_suit.auth.sanctum')) {
            Event::listen(
                TokenAuthenticated::class,
                [TokenAuthenticatedListener::class, 'handle']
            );
        }
        $this->configureGuard();
    }

    /**
     * Configure the Sanctum authentication guard.
     *
     * @return void
     */
    protected function configureGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('sanctum', function ($app, $name, array $config) use ($auth) {
                return tap($this->createGuard($auth, $config), function ($guard) {
                    app()->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * Register the guard.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  array  $config
     * @return RequestGuard
     */
    protected function createGuard($auth, $config)
    {
        return new RequestGuard(
            new CacheGuard($auth, config('sanctum.expiration'), $config['provider']),
            request(),
            $auth->createUserProvider($config['provider'] ?? null)
        );
    }
}
