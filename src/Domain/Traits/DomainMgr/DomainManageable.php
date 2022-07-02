<?php

namespace Zeaven\EasySuit\Domain\Traits\DomainMgr;

use Zeaven\EasySuit\Domain\Core\DomainEntity;
use Zeaven\EasySuit\Domain\Traits\Sortable\Sortable;
use Arr;
use Illuminate\Database\Eloquent\Model;
use Str;

/**
 * 领域基础管理特性
 * 在领域引用此特性，添加常量配置如下：
 * const ENTITYS = [
 * 'feedback' => Feedback::class,
 * ];
 *
 * 将提供对feedback模型的crud功能，如
 * 1. createFeedback(array $data)
 * 2. queryFeedback(array $option), $option => ['where' => '', 'select' => '']
 * 3. getFeedback(int $id)
 * 4. updateFeedback(int $id, array $data)
 * 5. deleteFeedback(int $id)
 *
 *
 * @date    2020-07-16 13:07:47
 * @version $Id$
 */
trait DomainManageable
{
    protected static function getDomainManageables()
    {
        static $managers;
        if (empty($managers)) {
            $managers = defined('static::ENTITYS') ? static::ENTITYS : [];
        }
        return $managers;
    }

    public function __call(string $method, array $arguments)
    {
        $managerMethods = static::getDomainManageables();
        $name = preg_replace('/(create|query|get|update|delete|paginate)/', '', $method);
        $key = Str::camel($name);
        throw_on(!isset($managerMethods[$key]), 'Model not exists：' . $key);
        $model = $managerMethods[$key];
        $manageable = new $model();
        // 判断entity是否存在对应方法
        if (method_exists($manageable, $method)) {
            return $manageable->{$method}(...$arguments);
        }

        $method = str_replace($name, 'Manageable', $method);

        throw_on(!method_exists($this, $method), "Model method not found：{$method}");

        return $this->{$method}($manageable, ...$arguments);
    }

    public function createManageable($manageable, array $data)
    {
        return $manageable->create($data);
    }

    protected function buildManageable($manageable, array $option = [])
    {
        $traits = class_uses($manageable);
        $where = Arr::get($option, 'where');
        $orWhere = Arr::get($option, 'or_where');
        $select = Arr::get($option, 'select');
        $order_bys = Arr::get($option, 'order_by');
        return $manageable->when(
            $where,
            function ($query, $where) {
                $query->where($where);
            }
        )
            ->when(
                $orWhere,
                function ($query, $orWhere) {
                    $query->where($orWhere);
                }
            )
            ->selectWhen($select)
            ->when(
                !array_key_exists(Sortable::class, $traits),
                function ($query) use ($order_bys) {
                    if (blank($order_bys)) {
                        $query->latest();
                    } else {
                        foreach ($order_bys as $order_column => $order_type) {
                            $query->orderBy($order_column, $order_type);
                        }
                    }
                }
            );
    }

    /**
     * $option => ['select' => [], 'where' => [], 'order_by' => []]
     * @param  Model  $manageable [description]
     * @param  array  $option     [description]
     * @return [type]             [description]
     */
    public function queryManageable($manageable, array $option = [])
    {
        return $this->buildManageable($manageable, $option)
            ->get();
    }

    public function paginateManageable($manageable, array $option = [])
    {
        $size = Arr::pull($option, 'size', request()->get('size', 15));
        return $this->buildManageable($manageable, $option)
            ->paginate($size);
    }

    public function getManageable($manageable, int $id, array $columns = ['*'])
    {
        return $manageable->whereKey($id)->firstOrFail($columns);
    }

    public function updateManageable($manageable, int $id, array $data)
    {
        return $this->getManageable($manageable, $id)->update($data);
    }

    public function deleteManageable($manageable, int $id)
    {
        return $this->getManageable($manageable, $id)->delete();
    }
}
