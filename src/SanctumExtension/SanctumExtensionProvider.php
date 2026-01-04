<?php

namespace Zeaven\EasySuit\SanctumExtension;

use Laravel\Sanctum\Sanctum;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Zeaven\EasySuit\SanctumExtension\CacheGuard;
use Zeaven\EasySuit\SanctumExtension\CachePersonalAccessToken;
use Zeaven\EasySuit\SanctumExtension\Middleware\TokenRefreshAuthenticate;
use Zeaven\EasySuit\SanctumExtension\Listeners\TokenAuthenticatedListener;

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
        if (!class_exists(Sanctum::class)) {
            return;
        }
        $cfg = config('easy_suit.auth.sanctum');
        $defCfg = ['enable' => true, 'token_model' => CachePersonalAccessToken::class];
        if ($cfg === true) {
            $cfg = $defCfg;
        } else {
            $cfg += $defCfg;
        }
        if ($cfg['enable']) {
            Event::listen(
                TokenAuthenticated::class,
                [TokenAuthenticatedListener::class, 'handle']
            );
        }
        if ($cfg['token_model'] && class_exists($cfg['token_model'])) {
            Sanctum::usePersonalAccessTokenModel($cfg['token_model']);
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
            Auth::createUserProvider($config['provider'] ?? null)
        );
    }
}
