<?php

/**
 * UUIDModel.php
 *
 * Author: Guo
 * Email jonasyeah@163.com
 *
 * Date:   2019-08-16 15:00
 */

namespace Zeaven\EasySuit\Domain\Core;

use Zeaven\EasySuit\Domain\Core\Model as BaseModel;
use Zeaven\EasySuit\Domain\Traits\Common\ModelUUID;

/**
 * Zeaven\EasySuit\Domain\Core\UUIDModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Zeaven\EasySuit\Domain\Core\UUIDModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Zeaven\EasySuit\Domain\Core\UUIDModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\Zeaven\EasySuit\Domain\Core\UUIDModel query()
 * @mixin \Eloquent
 */
class UUIDModel extends BaseModel
{
    use ModelUUID;

    // 采用uuid作为主键id，方便数据迁移
    protected $keyType = 'string';
    public $incrementing = false;
}
