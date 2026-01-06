# 开发说明

## 目录结构

```
root
├─app // 应用目录
├─bootstrap // 启动目录
├─config // 配置目录
├─database // 数据库
├─deploy // 发布
├─public // 网站根目录
├─resources // 前端资源
├─routes // 路由
├─storage // 本地存储
├─tests // 测试
└─vendor // 第三方包
```

项目开发一般使用的目录有**app、config、database、routes、storage**

### app 目录结构

```
app
├─Common  // 核心扩展功能目录
├─Console // 命令行、任务调度
├─Domain  // 领域驱动
├─Exceptions // 异常处理
├─Http // 网络层
├─Jobs // 队列
├─Logics // 业务逻辑层
└─Traits // 业务特性层
```

正常工程保持如上目录结构，如果需要事件处理，则增加 EventBus 事件总线目录

### Http 网络层

```
Http
├─Controllers // 控制器
├─Middleware  // 中间件
├─Requests  // 请求对象
└─ResponseMappers // 响应资源映射
```

1. 网络层只能触发业务，不能包涵有业务逻辑，以便不同的接口能调用相同的业务逻辑
2. 参数验证和用户登录态信息只能在控制器层获取(如果使用领域服务，可在领域上下文直接获取)，这些不属于业务逻辑，请通过参数传递到业务层
3. 网络层可增加 Resources 目录，提供对响应数据结构做转换（不同的接口可能调用相同的业务逻辑，但返回结果不一致）

### Logics 业务逻辑层

业务逻辑层调用一个或多个领域服(Domain)务完成应用业务，对于数据展示等业务(主要为查询服务)，通过业务特性层(Traits)实现

**数据展示服务变动较大，不应该整合到领域服务层，后续可配合大数据统计服务实现各种报表**

### Controllers 与 Requests、Logics 与 Traits 关系

1. 一个控制器(Controllers)对应一个业务逻辑(Logics)；
2. 一个控制器对应多个请求对象(Requests)，即一个 Controller 文件有一个对应的 Requests 目录；
3. 一个业务逻辑对应多个业务特性，即一个 Logic 文件有一个对应的 Traits 目录(非必须)；

Traits目录文件代码器不会生成，可根据业务复杂度将逻辑折成多个Trait文件方便管理

```
Http
├─Controllers
│  └─Api
│    └─Login
│      └─LoginController.php
├─Requests
│  └─Api
│    └─Login // 目录
Logics
├─Api
│  └─Login
│    └─LoginLogic.php
Traits
└─Api
   └─Login // 目录
```

### 响应资源映射

在控制器中返回数据时，需要对字段进行转换时使用

参考 App\Common\Http\ResponseMappers\BaseResponseMapper.php 注释说明

### Common 核心扩展功能目录

```
Common
├─Config // 全局配置目录
├─Console // 全局命令、任务调度、代码生成器
├─Libs // 扩展
│  ├─Annotations 注解日志
│  ├─Validators 自定义参数验证
│  ├─ErrorCode  错误码服务
│  └─...其它
├─Http // 全局网络层（中间件）
├─Services // 自定义第三方服务
└─Providers // 全局服务提供者
```

1. Config 目录为默认配置，将合并工程目录下的 config，并覆盖相同键值的配置；
2. Libs // 扩展提供各种非业务的基础功能封装
3. Http // 提供网络层路由中间件
4. Providers // 核心服务提供者
5. Validators // 扩展 laravel 请求参数验证功能

Common 目录提供了一个可持续开发的项目模板，放在任何版本的 Laravel 下面都能提供一致的架构基础，从而快速配置新的 Laravel 项目

## 路由定义规则

1. 路由只能使用小写字母，多个单词使用“-”进行连接，禁止使用下划线；
2. 路由必须包括控制器和方法名，如 /api/login/logout login 为控制器，logout 为方法；
3. 路由地址可添加多层目录进行分类，如 /api/system/log/list 对应方法路径为 Api/System/LogController@list；
4. 接口请求方式使用 Get 和 Post，restful 接口除外；
5. 路由中的控制器名使用单数，restful 接口必须使用复数，如 /api/login/logout 为普通接口，/api/articles 为 restful 接口；

### restful 接口定义（管理后台使用）

restful 接口一般在管理后台使用，对应数据的增删改查，其他应用的接口不推荐使用

restful 接口定义规则如下：

- get /api/articles 文章列表，对应方法 index
- post /api/articles 创建文章, 对应方法 store
- get /api/articles/{aritcle_id} 获取文章信息，对应方法 show
- put /api/articles/{article_id} 更新文章, 对应方法 update
- delete /api/articles/{aritcle_id} 删除文章，对应方法 destroy

### 代码生成器使用

#### 接口同步代码生成

**在使用前，请先在.env添加postman的apitoken配置， POSTMAN_API_TOKEN=**

使用 Artisan 命令 pm:run 即可

选择从 postman 的集合目录中生成网络层代码，可以选择多级目录，选择「生成代码」或最后一级目录即开始生成代码

### 领域代码生成

1. php artisan gen:model 生成领域模型
2. php artisan gen:entity 生成领域实体
3. php artisan gen:service 生成领域服务
4. php artisan gen:ctx 生成领域上下文
5. php artisan gen:event 生成领域事件

所有生成命令可通过添加 --help 查看说明

## 注解日志

注解日志采用控制器方法添加注解的方式实现

日志默认使用laravel的日志服务，你可以在App\Common\Libs\Annotations\AnnoLogMiddleware.php 修改为保存到你想要的地方

如

```php
    use App\Common\Libs\Annotations\AnnoLog; // 必须引用注解命名空间

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

- uid
- mobile
- username
- nickname

## 参数获取

参数获取推荐两种方式，可查看Common\Libs\Request\RequestExtension.php说明

### 获取 value 数组

**数组返回顺序与Request对象定义的字段规则顺序一致**

```php
[$username, $page] = $request->params(['username', 'page'])
// 或
$params = $request->values(); // 返回对应Request对象定义的参数数组值
```

### 获取 key-value 数组

```php
$param = $request->params(false);    // $param=['username' => 'xxx', 'page' => xx]
```

使用代码生成器生成的Request代码如下：

```php
/**
 *
 * @authors generator
 * @date    2022-03-28 15:14:19
 */
class LoginRequest extends ApiRequest
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
            'password' => ['rule' => 'required|min:4'],
        ];
    }
}
```

## 抛出异常

请在对应语言包添加error_code.php错误码配置文件，参考Common\Libs\ErrorCode\config.php

错误信息支持本地化变更替换

```php
// 直接抛出错误码
throw_e(0x000001);
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

## 多语言实现

### 添加多语言中间件

1. 创建中间件 ApiLocale
2. 中间件判断请求头携带的语言信息，设置当前请求的语言
3. 将中间件添加到 api 全局中间件
4. 在目录 resources.lang 目录下编写对应语言的 json 文件
5. 分页、参数验证国际化参考 en 目录创建对应文件即可
6. error_code 国际化文件移动对应的语言目录下的error_code.php

## 自定义用户提供者

提供把用户信息和登录信息分开存放在不同的表，实现登录和业务信息分开

使用Auth::attempt()走登录信息表，Auth::user()获取用户信息表

详细查看Common\Libs\Auth\readme.md

## Sanctum和Jwt认证

### Sanctum认证扩展

添加自动刷新token机制，请查看Common\Libs\SanctumExtension\readme.md

### 使用Sanctum

> 1. 在app.php添加配置 App\Common\Libs\SanctumExtension\SanctumExtensionProvider::class,
> 2. 修改auth.php默认guard为web
> 3. 在需要认证的路由添加中间件'auth:sanctum'

### Jwt认证扩展

添加自动刷新Token机制

### 使用Jwt认证

> 1. 在app.php添加配置 App\Common\Libs\Jwt\JwtAuthencicateProvider::class,
> 2. 修改auth.php默认guard为jwt
> 3. 在需要认证的路由添加中间件'auth:jwt'

## DDD领域驱动说明

领域驱动脚手架，通过 php artisan gen: 查看说明

### 目录结构

```
Domain
├─Casts         // Model类型转换器
├─Core          // 领域核心基础包
├─Events        // 领域事件，通过 php artisan gen:event 创建
├─Module        // 领域模块，定义不同的领域，如UserCenter、System
└─Traits        // 领域公共特性，如排序、无限级代理等
```

每一个领域在Module里有一个独立的目录

### 单个领域说明

领域设计的标准需符合五大设计原则(SOLID)：

1. 相互独立，每个领域之间不应有直接依赖；
2. 每个领域的参数不应放在构造函数，需提供配置能力，一般放在Common/Config/domain.php文件中；
3. 领域内应在合适的地方触发事件，与其他领域或服务进行通信；
4. 一个领域可以向外暴露一个或多个服务，每个服务也应相互独立；
5. 领域内必须使用Laravel的IOC获取服务对象；

#### 目录结构

```
UserCenter
├─Context         // 领域上下文，通过 php artisan gen:ctx 创建
├─Entity          // 领域实体，通过 php artisan gen:entity 创建
├─Model           // 领域模型，通过 php artisan gen:model 创建
├─Service         // 领域服务，通过 php artisan gen:service 创建
└─Traits          // 领域特性，无脚手架，需手动创建目录和文件
```

##### Context

领域上下文，主要提供在领域内获取其他领域或服务的功能，或登录用户，在领域内通过 $this->ctx 获取上下文对象

以用户中心领域上下文说明，当登录用户需要获取系统配置领域的信息，则需要在上下文定义系统配置领域服务

```php
class AccountContext extends DomainContext
{
    // 在此处定义当前领域需要调用的外部服务或其他领域对象
    protected array $services = [
        'systemConfig' => SystemConfigService::class,   // 系统配置领域服务
        'sms' => SmsService::class,                     // 第三方短信服务
    ];
}

// 在领域内使用
$this->ctx->systemConfig->领域方法();
// 调用短信服务发送短信
$this->ctx->sms->send();
```

##### Entity

实体，在领域内所有数据库操作都是通过实体来完成，可以一个实体对应一个数据表，但大多数情况下是不必要的

定义实体可以按这几个标准：

1. 数据表能体现独立业务；
2. 数据表有独立存在的意义；

我们以用户中心领域来说明，用户中心有这么几个业务：用户相关的业务、管理员业务、代理业务和登录Token维护,

所以我们按照 MemberEntity、AdminEntity、AgentEntity、TokenEntity 划分了四个实体对应四个主要业务，

像用户的个人信息表 tb_member_profile，或用户与代理的关联表 tb_member_agent，这些表独立存在是无意义的，且无法体现独立业务，所以不需要实体

##### Model

模型，一个模型对应一个数据表，模型有以下几个特点：

1. 定义表信息；
2. 定义表属性转换或修改器；
3. 定义表关联属性；
4. 不要放业务逻辑在模型内；

##### Service

领域服务，领域对外暴露的服务接口，除此外，领域内的其他对象都不能直接被外部使用，如Context、Entity，但是Model可以被其他地方用于**查询**

#### 如何设计领域

以一个电商网站为例，电商网站可以大致分为：用户、商品、订单、物流几个模块，每个模块可以看成一个独立的小系统，

那么我们就初步拆分成：账户领域、商品领域、订单领域、物流领域。

然后我们看账户领域里面除了有个人信息外，还有会资金信息，可能是系统内的也可能是第三方如银行、微信、支付宝，那么我们可以从账户领域分出一个财务领域，专门负责用户的资金管理。

然后再看商品领域，商品会涉及到上下架和库存等信息，也可能是一个独立的进销存系统，这里我们可以把库存单独拆分出一个领域，因为商品上下架这些都是系统内部的，但是库存有可能是第三方，比如网站代理的第三方商品，那么库存领域负责对接不同的第三方代理，对于商品领域来说是透明的。

订单领域里面会有支付相关问题、但是订单领域是不关心支付的，订单只要关心它的状态是否完成就行，所以我们独立一个支付领域，

当用户生成一个商品订单，只要把商品订单的订单号给到支付领域，支付领域内部负责它的支付订单，支付订单完成后通过事件通知到订单领域去修改商品订单结果。

这样一个支付领域可以用在其他有支付需要的系统里，领域设计其他就是把一个系统拆成不同的独立模块，然后又可以组合不同的模块产生另一个系统。

现在我们来看整个电商网站拆分成几个领域：

1. 账户领域；
2. 财务领域；
3. 商品领域；
4. 库存领域；
5. 订单领域；
6. 支付领域；
7. 物流领域；

还可以加上优惠领域负责各种优惠策略，以及社交领域提供聊天功能。

其实领域的设计没有绝对的标准，还是要遵循单一职责，一个领域负责一种业务，当一个领域业务太过复杂，则可能需要进行拆分了。

#### 业务逻辑与领域逻辑与实体逻辑

实体里面应该写什么、领域服务应该写什么、业务逻辑又该写什么？

比如注册功能，因为所有数据库操作都是实体完成，那么注册逻辑都写在实体里，逻辑层和领域服务层就什么都不用写了，直接调用实体就行。

但是一个系统的注册功能是会不断变化扩展的，如短信注册、后台添加用户、第三方单点登录注册，加上这么多不同的注册功能，实体的逻辑可想而知有多复杂。

所以逻辑都写在实体里不合适，那么它们之前的界限怎么区分呢？

按照以下几点：

1. 实体只对数据表负责，实体体现的应该是数据库设计的逻辑，如比数据库设计的用户表关系有 tb_member、tb_member_profile，实体必须保证这两个表数据的一致性和完整性；

所以实体可以提供一个注册方法，可以传入需要的数据表字段，然后保存到数据库中，实体逻辑不需要关注外部怎么注册。

```php
    /**
     * 创建用户
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $password 登录密码
     * @param string $mobile 手机号码
     * @param array $others 其他数据 [email,avatar,disable,level,generate]
     * @return mixed
     */
    public function createMember(string $username, string $nickname, string $password, string $mobile, array $others = [])
    {
        // 这里使用laravel的加密功能，如果使用第三方加密验证方式，则应该把密码逻辑放在领域内
        $password = bcrypt($password);

        $generate = 0;
        if ($mobile) {
            throw_on(Account::whereMobile($mobile)->exists(), 0xf00262);
            $generate = preg_match('/100\d{6,}/', $mobile) ? 1 : 0;
        }
        if (isset($others['email'])) {
            throw_on(Account::whereEmail($others['email'])->exists(), 0xf00102);
        }
        throw_on(Account::whereUsername($username)->exists(), 0xf00112);

        $level = 1;
        $memeberData = $others + compact('username', 'password', 'nickname', 'mobile', 'level', 'generate');

        $user = $this->create($memeberData);
        $user->account()->create($memeberData);
        $user->memberProfile()->create([]);

        return $user;
    }
```

2. 领域只对领域之间的逻辑负责，要避免业务逻辑的不断变化带来的影响，即领域不为某一业务服务，要根据不同的注册业务需求提取出最基本的领域逻辑。

比如用户注册，账户领域要负责调用领域内的实体完成信息录入，以及调用或通知其他领域服务完成财务数据创建或发送注册奖励等逻辑，而到底是发短信注册，还是后台添加用户那是具体的业务需求。

```php
    /**
     * 注册用户
     * @param  string $username 用户名
     * @param  string $nickname 呢称
     * @param  string $password 密码
     * @param  string $mobile   手机号
     * @param  string $mcode    会员邀请码，与acode只选其一
     * @param  string $acode    员工邀请码，与mcode只选其一
     * @param  array  $others   其他数据 [email,avatar,disable,level,generate]
     * @return [type]           [description]
     */
    public function registerMember(
        string $username,
        string $nickname,
        string $password,
        string $mobile,
        string $mcode = '',
        string $acode = '',
        array $others = []
    ) {
        $member = locker_trans(
            __FUNCTION__ . $mobile,
            function () use ($username, $nickname, $password, $mobile, $mcode, $acode, $others) {
                $memberEntity = app(MemberEntity::class);
                $agentEntity = app(AgentEntity::class);
                // 创建用户实体
                $member = $memberEntity->createMember($username, $nickname, $password, $mobile, $others);
                // 创建用户代理身份，并关联邀请用户
                $agent = $agentEntity->createAgent($member, $mcode);
                $agent->load('parents.owner.admins.agent');
                // 如果有会员邀请码，则获取上级会员的代理员工邀请码
                $acode = data_get($agent, 'parents.0.owner.admins.0.agent.code', $acode);
                // 将用户关联到对应的后台代理员工下
                $this->associateAdmin($member, $acode);
                // TODO: 添加用户钱包
                // $this->ctx->wallet->createWallet($member);

                return $member;
            }
        );

        // 派发注册事件
        MemberRegisterEvent::dispatch($member);

        return $member;
    }
```

3. 业务逻辑当然是为了服务不同的业务需求，如短信注册，业务逻辑要负责处理好短信验证，然后调用领域服务完成注册，同样的如果是第三方单点注册，业务逻辑负责调用第三方服务完成验证并获取信息，然后将信息传给领域服务完成注册。总之业务逻辑是处理同一个功能在不同场景需求下的实现。

```php
    // 短信注册
    public function register(string $password, string $mobile, string $code, string $acode, string $mcode)
    {
        throw_on(empty($mobile), 0xf00302);
        throw_on(!(new SmsVerifycode($mobile))->verify($code), 0xf00212);

        $service = app(AccountService::class);
        $service->registerMember($mobile, $mobile, $password, $mobile, $mcode, $acode);

        return $this->login(compact('mobile', 'password'));
    }
```