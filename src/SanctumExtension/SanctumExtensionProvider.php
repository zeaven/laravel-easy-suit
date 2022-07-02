<?php

namespace Zeaven\EasySuit\SanctumExtension;

use Zeaven\EasySuit\SanctumExtension\Listeners\TokenAuthenticatedListener;
use Zeaven\EasySuit\SanctumExtension\Middleware\TokenRefreshAuthenticate;
use App\Http\Middleware\Authenticate;
use Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;

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
    }
}
