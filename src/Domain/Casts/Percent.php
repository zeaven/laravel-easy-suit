<?php

/**
 * 百分比转换
 *
 * @date    2020-07-24 12:07:31
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Percent implements CastsAttributes
{
    /**
     * 将取出的数据进行转换
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        return _bcdiv($value, 100);
    }

    /**
     * 转换成将要进行存储的值
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        if (floatval($value) <= 1) {
            return _bcmul($value, 100);
        }
        return $value;
    }
}
