<?php

namespace Zeaven\EasySuit;

use App\Exceptions\Handler;
use App\Http\Middleware\Authenticate;
use Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Stringable;
use Str;
use Symfony\Component\Finder\Finder;
use Zeaven\EasySuit\Auth\Authenticate as EasySuitAuthenticate;
use Zeaven\EasySuit\Auth\CacheEloquentUserProvider;
use Zeaven\EasySuit\Exceptions\Handler as EasySuitHandler;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
        __DIR__ . '/../config/easy_suit.php' => config_path('easy_suit.php'),
        __DIR__ . '/../config/error_code.php' => lang_path('en/error_code.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->loadCommands();
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/easy_suit.php', 'easy_suit');
        $this->mergeConfigFrom(__DIR__ . '/../config/sanctum.php', 'sanctum');

        $this->configException();


        $this->configAuth();

        $this->configStrable();

        $this->configRoute();
    }

    private function configException()
    {
        $this->app->bind(Handler::class, EasySuitHandler::class);
    }

    private function loadCommands()
    {
        $namespace = 'Zeaven\\EasySuit\\';
        $commands = [];
        foreach ((new Finder())->in(__DIR__ . '/Console/Commands')->files() as $command) {
            $realPath = $command->getRealPath();
            if (Str::contains($realPath, 'stubs')) {
                continue;
            }
            $commands[] = $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), 'laravel-easy-suit/src/')
            );
        }
        $this->commands($commands);
    }

    private function configAuth()
    {
        $this->app->bind(Authenticate::class, EasySuitAuthenticate::class);

        // 自定义用户提供者，默认每次通过token查询用户是否存在，自定义提供者可在查询中增加缓存，减少数据库查询，但是用户状态更新不及时
        // 需要手动调用CustomEloquentUserProvider::refresh($key)清除登录缓存
        Auth::provider(
            'cache_eloquent',
            function ($app, array $config) {
                // 返回 Illuminate\Contracts\Auth\UserProvider 实例...
                $model = $config['model'];
                $fields = $config['fields'] ?? null;
                $authModel = $config['auth_model'] ?? $config['model'];

                return $app->make(CacheEloquentUserProvider::class, compact('model', 'authModel', 'fields'));
            }
        );
    }

    private function configStrable()
    {
        /**
         * 字符串增强
         * Str::replaceMatch('{foo} {bar}', ['foo' => 1, 'bar' => ]) ==> '1 2'
         * @var [type]
         */
        Str::macro(
            'replaceMatch',
            function (string $subject, array $replacements) {
                return preg_replace_callback(
                    "/{([^{}]+)}/",
                    function ($matches) use ($replacements) {
                        $matche = $matches[1];
                        foreach ($replacements as $key => $value) {
                            if ($key === $matche) {
                                return $value;
                            }
                        }
                        return $matches[0];
                    },
                    $subject
                );
            }
        );
        Stringable::macro(
            'replaceMatch',
            function (array $replacements) {
                return preg_replace_callback(
                    "/{([^{}]+)}/",
                    function ($matches) use ($replacements) {
                        $matche = $matches[1];
                        foreach ($replacements as $key => $value) {
                            if ($key === $matche) {
                                return $value;
                            }
                        }
                        return $matches[0];
                    },
                    $this->value()
                );
            }
        );
    }

    private function configRoute()
    {
        Route::macro(
            'configRoute',
            function (string $name, string $prefix = '/', array $middleware = []) {
                $namespace = "App\\Http\\Controllers\\" . ucfirst($name);
                Route::prefix($prefix)
                    ->middleware(empty($middleware) ? $name : $middleware)
                    ->namespace($namespace)
                    ->domain(config('app.url'))
                    ->group(base_path("routes/{$name}.php"));
            }
        );
    }
}
