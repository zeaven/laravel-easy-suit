<?php

/**
 * 验证扩展
 *
 * @date    2019-03-13 18:24:39
 * @version $Id$
 */

namespace Zeaven\EasySuit\Validators;

use Str;
use Validator;

class ValidatorExtension
{
    public static function add($class, ?string $name = null)
    {
        if (empty($name)) {
            $name = Str::snake(str_replace('Validator', '', class_basename($class)));
        }
        Validator::extend($name, $class . '@validate');

        Validator::replacer($name, $class . '@replacer');
    }
}
