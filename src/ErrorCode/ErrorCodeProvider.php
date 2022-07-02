<?php

/**
 * 错误码
 *
 * @date    2018-10-31 17:45:11
 * @version $Id$
 */

namespace Zeaven\EasySuit\ErrorCode;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Request;

class ErrorCodeProvider extends ServiceProvider // implements DeferrableProvider
{
    public static $abstract = 'error_code';
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration file
        // if ($this->app->runningInConsole()) {
        //     $this->publishes([
        //         __DIR__ . '/config.php' => config_path(static::$abstract . '.php'),
        //     ]);
        // }
    }

    public function register()
    {
        // $this->mergeConfigFrom(__DIR__.'/config.php', static::$abstract);

        $this->app->bind(static::$abstract, function () {
            return new ErrorCode();
        });

        Request::macro(
            'errorData',
            function (?array $data = null) {
                if ($data) {
                    $this->attributes->set(ErrorCodeProvider::$abstract, $data);
                } else {
                    return $this->attributes->get(ErrorCodeProvider::$abstract);
                }
            }
        );
    }

    /**
     * 取得提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [static::$abstract];
    }
}
