# Laravel Easy Suit
这是一个为了方便使用Laravel框架开发api而提供的简单封装套件。


## 安装


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


## 错误码和异常抛出

### 错误码
