<?php

/**
 * 自动排序
 * @authors master (master@v8y.com)
 * @date    2021-05-27 09:04:42
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Sortable;

use Zeaven\EasySuit\Domain\Traits\Sortable\AutoSortableScope;

trait AutoSortable
{
    public static function bootAutoSortable()
    {
        // 添加查询作用域
        static::addGlobalScope(new AutoSortableScope());
    }
}
