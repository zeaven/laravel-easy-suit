<?php

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
    'f00042' => '登录失败，请检账号或密码是否正确',
    'f00052' => '登陆用户类型不存在！',
    'f00212' => '验证码不正确',
    'f00222' => '验证码已发送，请稍候',
    'f00230' => '微信用户未绑定手机号',
    'f00232' => '短信发送失败',
    'f00242' => '用户手机号禁止登录',
    'f00252' => '账号异常，请联系客服',
    'f00262' => '手机号已绑定',
    'f00102' => '邮箱已注册',
    'f00112' => '用户名已注册',
    'f00122' => '用户不存在',
    'f00132' => '密码不一致',
    'f00142' => '登录失败',
    'f00152' => '找不到该数据',
    'f00172' => '密码错误',
    'f00182' => '原密码错误',
    'f00192' => '注册用户失败，系统繁忙',
    'f00402' => '只能添加一个收款账户',
];
