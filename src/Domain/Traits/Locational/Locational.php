<?php

/**
 *
 *
 * @date    2019-09-24 12:36:52
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Locational;

/**
 * 省市区联动，使用国家统一格式
 */
trait Locational
{
    public static function bootLocational()
    {
        // 添加查询作用域
        static::addGlobalScope(new LocationalScope());
    }
}
