<?php

/**
 * 手机号遮掩
 *
 * @date    2020-06-19 16:21:39
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\MobileMask;

use Zeaven\EasySuit\Domain\Traits\MobileMask\MobileMaskScope;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait MobileMask
{
    public static function bootMobileMask()
    {
        // 添加查询作用域
        static::addGlobalScope(new MobileMaskScope());
    }

    protected function mobile(): Attribute
    {
        $scope = static::getGlobalScope(MobileMaskScope::class);
        return new Attribute(
            get: fn ($value) => empty($scope->_mobileMask) ? $value
                : preg_replace('/(\d{3})(\d{4})(\d{1,})$/', '$1' . str_repeat($scope->_mobileMask, 4) . '$3', $value),
        );
    }
}
