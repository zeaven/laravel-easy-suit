<?php

/**
 *
 *
 * @date    2019-03-13 18:25:58
 * @version $Id$
 */

namespace Zeaven\EasySuit\Validators;

interface Validator
{
    /**
     * 验证规则
     * @param  string $attribute  参数名
     * @param  string $value      参数值
     * @param  array $parameters  规则参数，如规则size:1,20，则参数为[1,20]
     * @param  object $validator  验证实例
     * @return bool               是否通过验证
     */
    public function validate($attribute, $value, $parameters, $validator): bool;

    /**
     * 验证错误信息字符串替换
     * @param  string $message    验证错误信息
     * @param  string $attribute  参数名
     * @param  string $rule       参数规则
     * @param  array $parameters  验证参数
     * @param  object $validator  验证实例
     * @return string             返回替换后的错误信息
     */
    public function replacer($message, $attribute, $rule, $parameters, $validator): string;
}
