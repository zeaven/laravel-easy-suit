<?php

/**
 * 订单状态
 *
 * @date    2019-10-30 09:24:58
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Appendable;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * 针对model中某一个状态字段做转换
 */
trait StatusText
{
    protected function initializeStatusText()
    {
        $this->append('status_text');
    }

    private function getStatus()
    {
        return defined('static::STATUS') ? static::STATUS : [];
    }

    private function getStatusMin()
    {
        return defined('static::STATUS_MIN') ? static::STATUS_MIN : 0;
    }

    protected function statusText(): Attribute
    {
        $min = $this->getStatusMin();
        $types = $this->getStatus();
        return new Attribute(
            get: fn ($_, $attributes) => $types[intval($attributes['status'] ?? -99) - $min] ?? '',
            set: fn ($value) => ['status' => $min + (array_search($value, $types) ?: 0)],
        );
    }
}
