## Sanctum 验证说明

Sanctum验证分为同源和第三方请求

### 同源验证

> 同源验证下需要先请求CSRF保护路由，即 /sanctum/csrf-cookie
> HTTP库会设置cookie的X-XSRF-TOKEN请求头，可参考官方说明
> 使用HasApiTokens的createToken创建accessToken后，会自动将用户标志写入session
> 后续请求会尝试从session读取用户标志，并以 sanctum.guard 配置的Guard恢复用户信息（对应的Guard必须是session驱动）
> 
> **注：同源情况下无需设置 Authorization 请求头**

> 如果session过期，则授权失效
> 此时如果请求头带有 Authorization认证，sanctum也会使用自身的Guard通过token恢复用户信息


### 第三方请求

> 不在sanctum.stateful配置的安全域下，如移动端请求是无法通过CSRF保护和sessoin恢复用户标识的
> 这时候需要在请求头添加Authorization认证信息


### 自动刷新Token

> sanctum的token默认有个过期时间配置 sanctum.expiration，过期则无法再次使用
> 如果设置过期时间太长，则安全性太低
> 因此添加一个自动刷新token功能，增加如下配置到 sanctum.php
> 
> ```php
>     'expiration' => 20160,  // 两周过期时间
>     'refresh_ttl' => 60,    // 一个小时刷新一次token
>     'refresh_grace_ttl' => 5, // 刷新token的灰色时间，防止同一token并发多个请求刷新多次
>     'remove_refresh_expire_token' => true,  // 是否移除刷新后的token
> ```
> 
> 如上配置，token将会在每小时刷新一次，每次有效期是两周
> 即两周内有访问，token有效期就可以一直往后延
> 每次刷新后，原来的token也可以选择是否需要删除
> 
> **注：自动刷新token必须添加Authorization请求头**
> 
>> 每次刷新token后，请求的响应头会添加 Authorization 返回新的token，将其替换掉原来的token即可

### 指定验证路由

> 验证方式对应的Provider会替换掉默认的Authenticate对象，以接管验证逻辑
> 但是默认的路由或第三方包提供的接口不希望验证受到影响
> 所以指定了Api或Admin下的控制器才走自定义验证，可自行修改其他路由地址