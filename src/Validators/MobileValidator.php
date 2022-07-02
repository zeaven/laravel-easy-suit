<?php

/**
 * 手机号验证
 * Date: 2019/3/27
 * Time: 16:44
 */

namespace Zeaven\EasySuit\Validators;

class MobileValidator implements Validator
{
    public function validate($attribute, $value, $parameters, $validator): bool
    {
        if (preg_match('/^100\d{6,}$/', $value)) {
            return true;
        }
        return  preg_match("/^1[34578]\d{9}$/", $value);
    }

    public function replacer($message, $attribute, $rule, $parameters, $validator): string
    {
        return str_replace(':mobile', request()->input($attribute), $message);
    }
}
