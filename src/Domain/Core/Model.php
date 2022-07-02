<?php

namespace Zeaven\EasySuit\Domain\Core;

use Str;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Zeaven\EasySuit\Domain\Traits\Sortable\AutoSortable;
use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 * laravel 框架基础模型
 *
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model query()
 */
class Model extends BaseModel
{
    use AutoSortable;

    protected $guarded = ['id'];
    protected $connection = 'mysql';
    protected $perPage = 15;

    /**
     * 将表名映射为单数，如:模型 User => 表 user
     *
     * @return string
     */
    // public function getTable()
    // {
    //     return $this->table ?? Str::snake(class_basename($this));
    // }

    /**
     * Prepare a date for array / JSON serialization.
     * 序列化日期格式
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * 批量导入数据
     * @param array $data [description]
     */
    public function addAll(array $data)
    {
        return DB::table($this->getTable())->insert($data);
    }
}
