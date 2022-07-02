<?php

/**
 *
 * @authors zeaven (zeaven@163.com)
 * @date    2019-08-06 14:07:23
 * @version $Id$
 */

namespace Zeaven\EasySuit;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class Deployer
{
    public static function postDeploy(Event $event)
    {
        $env = $event->getArguments()[0] ?? 'local';

        $file_path = '.env.example';
        $content_lines = file($file_path);

        if ($env !== 'product' && $env !== 'production') {
            echo '>>>>>>>>>更新环境变量配置文件……' . PHP_EOL;
            $config = static::configure($env);

            if ($config) {
                foreach ($content_lines as $line => $content) {
                    if (empty($content) || strpos($content, '#') !== false) {
                        continue;
                    }
                    [$key] = explode('=', $content);

                    if (isset($config[$key])) {
                        $content_lines[$line] = "{$key}={$config[$key]}" . PHP_EOL;
                    }
                }
            }
            file_put_contents('.env', implode('', $content_lines));
        }

        // static::installPackage();
        if ($env === 'product' || $env === 'production') {
            static::optimize();
            // echo PHP_EOL. '>>>>>>>>>发布sentry版本……'. PHP_EOL;
            // system('./deploy/sentry.sh');
        }
        system('php artisan route:permission'); // 生成权限
        // system('php artisan queue:restart');    // 重启队列
        echo PHP_EOL . '>>>>>>>>>发布完成。' . PHP_EOL;
    }

    protected static function optimize()
    {
        echo PHP_EOL . '>>>>>>>>>缓存编译、配置、路由，刷新队列、OpCache……' . PHP_EOL;
        system('php artisan optimize');
        system('php artisan config:cache');
        system('php artisan api:cache');
        system('php artisan event:cache');
        // system('phpbrew fpm restart');  // 重启php服务，刷新opcache，需要构建的时候执行，在php代码中无法重启php
    }

    public static function installPackage()
    {
        echo PHP_EOL . '>>>>>>>>>清除laravel 编译缓存……' . PHP_EOL;
        system('php artisan clear-compiled');
        // 读取上次文件
        $md5 = md5_file('composer.lock');
        $old_md5 = '';
        if (file_exists('./deploy/deploy.lock')) {
            $old_md5 = file_get_contents('./deploy/deploy.lock');
        }
        if ($old_md5 !== $md5) {
            echo PHP_EOL . '>>>>>>>>>更新依赖包……' . PHP_EOL;
            system('php composer.phar install');
            file_put_contents('./deploy/deploy.lock', $md5);
        } else {
            echo PHP_EOL . '>>>>>>>>>dump package……' . PHP_EOL;
            system('php composer.phar dump');
        }
    }
}
