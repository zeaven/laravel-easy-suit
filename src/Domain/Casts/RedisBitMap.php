<?php

namespace Zeaven\EasySuit\Domain\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * 字段加密特性
 *
 * @date    2020-08-07 10:24:23
 * @version $Id$
 */
class RedisBitMap implements CastsAttributes
{
    /**
     * 将取出的数据进行转换
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        $days = $model->calendar->daysInMonth;
        $map = $value ? decbin($value) : '';
        return str_pad($map, $days, '0', STR_PAD_LEFT);
    }

    /**
     * 转换成将要进行存储的值
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param array $value
     * @param array $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return strlen($value) > 18 ? bindec($value) : $value;
    }
}
