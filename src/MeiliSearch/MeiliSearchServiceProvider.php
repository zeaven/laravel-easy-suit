<?php

namespace Zeaven\EasySuit\MeiliSearch;

use Zeaven\EasySuit\MeiliSearch\Factory\RequestFactory;
use Illuminate\Support\ServiceProvider;
use MeiliSearch\Client as MeiliSearch;

class MeiliSearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 修复MeiliSearch-php缺少api key
        if (class_exists(MeiliSearch::class)) {
            $this->app->scoped(MeiliSearch::class, function ($app) {
                $config = $app['config']->get('scout.meilisearch');
                $apiKey = ($config['apikey'] ?? '') ?: $config['key'];
                return new MeiliSearch($config['host'], $config['key'], null, new RequestFactory($apiKey));
            });
        }
    }
}
