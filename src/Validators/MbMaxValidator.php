<?php

/**
 *
 * 中文字符长度验证
 * @date    2019-03-13 18:27:27
 * @version $Id$
 */

namespace Zeaven\EasySuit\Validators;

class MbMaxValidator implements Validator
{
    public function validate($attribute, $value, $parameters, $validator): bool
    {
        [$max] = $parameters;
        return mb_strlen($value) <= $max;
    }

    public function replacer($message, $attribute, $rule, $parameters, $validator): string
    {
        return str_replace(':mb_max', $parameters[0], $message);
    }
}
