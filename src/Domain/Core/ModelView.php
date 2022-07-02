<?php

namespace Zeaven\EasySuit\Domain\Core;

use Zeaven\EasySuit\Domain\Core\Model as BaseModel;
use Zeaven\EasySuit\Domain\Traits\Sortable\AutoSortable;

/**
 * laravel 框架基础模型
 *
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model query()
 */
class ModelView extends BaseModel
{
    protected $connection = 'mysql_view';
}
