<?php

namespace Zeaven\EasySuit\Domain\Core;

use Illuminate\Database\Eloquent\Relations\Pivot as BasePivot;
use Str;
use DateTimeInterface;

/**
 * laravel 框架基础模型
 *
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Commons\Models\Model query()
 */
class Pivot extends BasePivot
{
    protected $guarded = ['id'];
    protected $connection = 'mysql';
    protected $perPage = 10;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? Str::snake(class_basename($this));
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
