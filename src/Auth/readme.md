## 用户提供者

一般的用户数据库设计都会有一个user表，里面包含用户名和登录密码信息
可能还会有手机号登录、email登录甚至微信绑定登录等等，所以user表会越来越多信息，如

|user|
|----|
|id|
|username|
|mobile|
|email|
|wx_union_id|
|password|
|...其他|

因为用户信息表会经常用到，但是登录信息并不是
因此可以拆成用户信息表和账号表，即 user <-> account，关联字段为uid，也可以使用任何你想要的字段

|user|             |account|                       
|----|             |----|                     
|id|               |id|     
|uid|              |uid|         
|username|         |username|         
|nickname|         |email|     
|email|            |wx_union_id|      
|gender|           |password|
|...其他|           |last_login_at|


> user表只保存用户信息，如用户名、手机号、呢称、性别、年龄等
> account表只保存登录相关信息，如用户名、手机号、email、密码、最后登录时间等
> account表只在登录时使用，其他业务很少会使用到

为了使用这种设计，如果通过account登录，默认的 User Provider 会返回account信息，并绑定到Auth服务里
每次想获取登录用户信息都得通过 Auth::user()->user 的方式，比较笼统（Auth::user()其实是account记录)
因此自定义一个 User Provider，通过 account 表验证，返回 user 表信息到Auth服务

### 注册服务提供者

在合适的地方，把CacheEloquentUserProvider注册到 Auth服务里，可以在AuthServiceProvider，也可以是CommonServiceProvider
这里默认已经在CommonServiceProvider注册了

```php
Auth::provider(
    'cache_eloquent',
    function ($app, array $config) {
        // 返回 Illuminate\Contracts\Auth\UserProvider 实例...
        $model = $config['model'];
        $fields = $config['fields'];
        $authModel = $config['auth_model'];

        return $app->make(CacheEloquentUserProvider::class, compact('model', 'authModel', 'fields'));
    }
);
```

### 配置 User Provider

在 config/auth.php 里添加一个 providers 配置，名为 cache, 并且对应的Guard指定provider为cache

```php
[
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'accounts',
        ],
        'api' => [
            'driver' => 'session',
            'provider' => 'cache',
        ]
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Domain\Module\UserCenter\Model\User::class,
        ],
        'cache' => [
            'driver' => 'cache_eloquent',
            'model' => App\Domain\Module\UserCenter\Model\User::class,
            'auth_model' => App\Domain\Module\UserCenter\Model\Account::class,
            'fields' => ['id','uid','username', 'nickname'],
        ],
    ]
]
```

> - driver就是已经注册的 User Provider
> - mode为返回的具体模型，即User表
> - auth_model为验证使用的模型，即Account表
> - fields指定返回user表的列数据，填 \['*'\] 或为 null返回所有列数据


