# Laravel Easy Suit
这是一个为了方便使用Laravel框架开发api而提供的简单封装套件。


## 安装



## Postman 代码生成器

使用前先在.env文件添加postman的apiToken：

POSTMAN_API_TOKEN=xxx

然后执行 artisan pm:run

![pm:run使用](https://raw.githubusercontent.com/zeaven/laravel-easy-suit/main/image/pm.png)

postman接口定义如下:

![postman接口定义](https://raw.githubusercontent.com/zeaven/laravel-easy-suit/main/image/postman.png)

生成的控制器代码：
![controller](https://raw.githubusercontent.com/zeaven/laravel-easy-suit/main/image/controller.png)

生成的Request代码：
![request](https://raw.githubusercontent.com/zeaven/laravel-easy-suit/main/image/request.png)

其他文件不一一展示，接口代码生成后，只需要配置参数验证规则，和在Logics目录编写业务逻辑代码即可

路由配置自动添加，但是中间件需要自行配置



## Request封装

BaseRequest继承于FormRequest，增加一个rule方法配置参数规则。


### 定义Request对象

```php
use Zeaven\EasySuit\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    /**
     * 返回参数验证规则.
     *
     * @return array
     */
    protected function rule(): array
    {
        return [
            // 用户名
            'username' => ['rule' => 'required'],
            // 密码
            'password' => ['rule' => 'required|min:6']
        ];
    }
}
```

Request对象提供两个方法获取参数：params和values，*注意：未配置的参数是无法通过这两个方法获取的*

```php
[$username, $password] = $request->params();
// 或
[$username] = $request->params(['username']);
// 或
[$password, $username] = $request->params(['password', 'username']);
// 或
$params = $request->params(false);
// 等同
$params = $request->values();
// $params = ['username' => 'xxx', 'password' => 'xxx']
```


### Request对象参数配置

rule方法返回参数的配置，完整配置字段如下：
```php
[
    'username' => [
        'rule' => 'required',
        'default' => 'admin',
        'type' => 'string',
        'as' => 'login_name'
    ],
    'remember' => true, // 等同于 'remember' => ['default' => true]
    'password'  // 等同于‘password’ => ['default' => null]
]
```

> 1. rule 与Laravel的表单验证规则一致；
> 2. default 默认值；
> 3. type 参数类型，可选值有：int、float、bool、array(json)、date、ip、url、split(将字符串转以逗号分割成数组)；
> 4. as 别名，使用values()方法返回的key值；



## 全局返回统一格式

在使用前需要先添加路由中间件：

```php
    protected $middlewareGroups = [
        'web' => [
        ],

        'api' => [
            \Zeaven\EasySuit\Http\Middleware\GlobalResponse::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

```

在easy_suit.php配置文件中有如下默认配置:

```php
'global_response' => [
    'fields' => [
        'code' => 'code',
        'data' => 'data',
        'message' => 'msg',
        'error' => false, // error只有在debug环境下有效
    ],
    'exclude' => [
        'horizon/*',
        'laravel-websockets/*',
        'broadcasting/*',
        '*/export/*',
        '*/pusher/auth',
        '*/pusher/auth',
        'web/*',
    ]
],
```

> fields 指定返回的字段，以及字段名称，如果定义为false则不显示
> exclude 可定义排除的路由

在控制器中 调用ok()全局方法，返回即可。



## 错误码和异常抛出

### 错误码定义

在error_code.php配置文件中定义错误码信息，下面的规则只是参考，至于你喜欢多少位的错误码，完全由你决定

错误码会发布到lang目录下，因为它是支持多语言的。

```php
/**
 * 错误码定义样例，请不要在这里定义错误码！应在对应的语言包目录下创建error_code.php文件！
 * 错误码以十六进制方式定义，如f00000:
 * -- 第1位：项目 f全局、1~e自行分项目，如1ios、2android、3web、4小程序
 * -- 第2-3位：模块 00全局、其他数字自行定义，如01登录、02订单、03钱包
 * -- 第4-5位：错误码，如00~ff都可以使用
 * -- 第6位：提示码，0忽略错误、1客户端弹窗提示、2客户端toast提示
 * 出现error_code=0,则后台未知错误
 *
 * 比如定义ios端登录模块错误码： 101012 => '用户名错误'， 101022 => '用户密码错误'
 */

return [
    '401'    => '未授权',
    '500'    => '查询出错',
    'f00002' => 'token已过期',
    'f00012' => '用户不存在',
    'f00022' => 'token无效',
    'f00032' => '缺少登录信息',
];
```

### 异常抛出

使用全局方法在你需要的地方抛出异常

```php
// 直接抛出错误码
throw_e(0x000001);
throw_e(401);
// 抛出异常信息
throw_e('异常信息');
// 指定错误信息和错误码
throw_e('异常信息', 0x000001);
// 空条件抛出
throw_empty($user, 0x000001);   // $user变量为空则抛出异常
throw_empty($user, '异常信息');   // 同上
throw_empty($user, '异常信息', 0x000001);   // 同上
// 判断条件抛出
throw_on($user->status === -1, 0x000001);
// 或
throw_on($user->status === -1, '异常信息');
throw_on($user->status === -1, '异常信息', 0x000001);
```

所有异常抛出方法的最后一个参数可以传入一个数组，用于本地化参数替换，[替换翻译字符串中的参数](https://learnku.com/docs/laravel/9.x/localization/12232#replacing-parameters-in-translation-strings)



## 注解日志

注解日志采用控制器方法添加注解的方式实现，在使用前需要先添加路由中间件：

```php
    protected $middlewareGroups = [
        'web' => [
        ],

        'api' => [
            \Zeaven\EasySuit\Annotations\AnnoLogMiddleware::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

```


日志默认使用laravel的日志服务，你可以在Zeaven\EasySuit\Annotations\AnnoLogMiddleware.php 修改为保存到你想要的地方

config('easy_suit.anno_log.enable') 配置项控制是否开启日志

### 在控制器使用日志

当此方法有请求时，日志会在返回用户结果后保存

```php
    use Zeaven\EasySuit\Annotations\AnnoLog; // 必须引用注解命名空间

    #[AnnoLog(type:1, tpl:"{mobile},{type}审核提现,订单号{order_no},签名{sign}")]
    public function index(TestRequest $request)
    {
        // 设置日志模板变量
        anno_log(['order_no' => 'test', 'sign' => 'sign']);
        // 或
        anno_log('order_no', 'test');
        anno_log('sign', 'sign');
    }
```

### 内置注解模板变量

在用户登录状态下，登录用户模型的缓存字段信息将自动添加到模板变量，可直接使用，如：

-   uid
-   mobile
-   username
-   nickname



## 用户认证

### 配置

在easy_suit.php文件中
```php
    'auth' => [
        'sanctum' => true,
        'jwt' => [
            'enable' => true,
            'guard' => 'jwt'
        ],
    ],
```


### 自动刷新Token

内置的Authenticate对Token验证的同时，如果token超过刷新周期，

则会自动刷新对应的Token，通过响应头下发给客户端，所以客户端应该在请求成功回调中，判断响应头是否包含“Authorization”字段，有的话，记得刷新本地Token。

```javascript
$axios.onResponse((response) => {
    // 刷新token
    if ('authorization' in response.headers) {
      // eslint-disable-next-line no-unused-vars
      const [_, token] = response.headers.authorization.split(' ')
      store.commit('SET_TOKEN', token)
    }
    return Promise.resolve(response.data)
  })
````


 **注：自动刷新token必须在客户端请求中添加Authorization请求头**


### Sanctum认证

在配置文件中启用Sanctum认证后，将会使用内置的Authenticate接管认证过程，

同时为Sanctum认证增加自动刷新token功能，具体配置项如下；

> 
> ```php
>     'expiration' => 20160,  // 两周过期时间
>     'refresh_ttl' => 60,    // 一个小时刷新一次token
>     'refresh_grace_ttl' => 5, // 刷新token的灰色时间，防止同一token并发多个请求刷新多次
>     'remove_refresh_expire_token' => true,  // 是否移除已刷新的token
> ```
> 
> 如上配置，token将会在每小时刷新一次，每次有效期是两周
> 即两周内有访问，token有效期就可以一直往后延
> 每次刷新后，原来的token也可以选择是否需要删除


### 使用Sanctum认证

在路由配置中添加auth中间件
```php
Route::middleware('auth:sanctum')->group(function () {
    // 你的路由
});
```

### JWT认证

#### 安装JWT第三方包

Laravel 9.x 不支持 tymon/jwt-auth 包，但可以指定开发版
```php
composer require "tymon/jwt-auth:dev-develop"
```

#### 配置JWT

按照tymon/jwt-auth配置，在auth.config添加jwt的守卫配置后
```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'jwt' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ]
    ],

```

在easy_suit.php配置文件中，也把auth.jwt.guard改成你添加的守卫名称，这里都是"jwt"


### JWT认证

在配置文件中启用JWT认证后，将会使用内置的Authenticate接管认证过程，

同时为JWT认证增加自动刷新token功能。


### 使用JWT认证

在路由配置中添加auth中间件
```php
Route::middleware('auth:jwt')->group(function () {
    // 你的路由
});
```



## ResponseMapper 资源映射



## Model扩展

在easy_suit.php配置扩展开关

```php
    'model' => [
        // 或 'simple_pagination' => '自定义分页对象'
        'simple_pagination' => true,
        'extension' => true
    ]

```

### 开启分页简化

Laravel默认分页对象返回的字段过多，开启简化后只返回两个字段"items"、"total"

### 开启扩展

为Laravel Model增加几个扩展方法

#### withs

Laravel 9.18 版本以上推荐直接使用官方的with方法。

withs 用于加载嵌套模型，假如有模型关系 A<-B<-C<-D。

```php
A::with('b.c.d')
// 等同于
A::withs('b', 'c', 'd')
// 等同于
A::withs(['b', 'c', 'd'])

// 加载关系模型时选择字段
A::withs('b:col1,col2,col3', 'c', 'd:col1,col2')
// 等同于
A::withs(['b:col1,col2,col3', 'c', 'd:col1,col2'])

// 载关系模型时增加筛选条件
A::withs([
    'b:col1,col2,col3' => fn($q) => $q->where('col1', 'xxx'),
    'c' => fn($q) => $q->select('col1','col2','col3'),
    'd:col1,col2' => fn($q) => $q->orderBy('col3')
])
```

#### selectWhen

如果指定字段数组不为空则使用，否则返回所有字段

```php
$fields = [];
A::selectWhen($fields)->get();
```

#### whereWhen

如果指定过滤条件数组不为空则使用，常用在表格筛选提交的条件中

```php
$options = ['col1' => 1, 'col2' => 'xxx'];
A::whereWhen($options)->get();
```

#### whenFilled

当给定参数有值时，执行回调

```php
$a = 1;
$col1 = 'xxx';
A::whenFilled($a, $col1, function($q, $a, $col1) => {
    $q->where('col1', $col1)->where('col2', $a);
})->get();
```

#### whenBetween

```php
A::whenBetween('col1', 1, 10)->get();
// select * from a where col1 between 1, 10

A::whereBetween('col1', 1)->get();
// select * from a where col1 >= 1

A::whereBetween('col1', null, 10)->get();
// select * from a where col1 <= 10

```

#### whenLike

当给定参数有值，则对指定字段模糊查询

```php
A::whenLike('col1', 'xyz')->get();
// select * from a where col1 like '%xyz%'
```