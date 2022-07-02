<?php

/**
 * 自动排序作用域
 * @authors master (master@v8y.com)
 * @date    2021-05-27 09:06:02
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Sortable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AutoSortableScope implements Scope
{
    protected $extensions = ['autoSort', 'unAutoSort'];
    /**
     * 把约束加到 Eloquent 查询构造中。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addUnAutoSort(Builder $builder)
    {
        $builder->macro(
            'unAutoSort',
            function (Builder $builder) {
                return $builder->withoutGlobalScope($this);
            }
        );
    }

    protected function addAutoSort(Builder $builder)
    {
        $builder->macro(
            'autoSort',
            function (Builder $builder) {
                $sorter = request()->getSort();
                if ($sorter && $sorter['field']) {
                    $builder->reorder()->orderBy($sorter['field'], $sorter['order'] ?? 'asc');
                }
                return $builder;
            }
        );
    }
}
