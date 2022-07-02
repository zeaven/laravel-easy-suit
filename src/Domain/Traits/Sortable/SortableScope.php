<?php

/**
 *
 *
 * @date    2019-09-16 15:10:50
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Sortable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SortableScope implements Scope
{
    private $sort_field;

    public function __construct($sort_field)
    {
        $this->sort_field = $sort_field;
    }

    protected $extensions = ['sortAsc', 'sortDesc', 'withoutSort', 'fixSort'];

    /**
     * 把约束加到 Eloquent 查询构造中。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->latest($model->getSortNumColumn());
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

    protected function addWithoutSort(Builder $builder)
    {
        $builder->macro(
            'withoutSort',
            function (Builder $builder) {
                return $builder->withoutGlobalScope($this);
            }
        );
    }

    protected function addSortAsc(Builder $builder)
    {
        $builder->macro(
            'sortAsc',
            function (Builder $builder) {
                return $builder->withoutGlobalScope($this)->oldest($this->sort_field);
            }
        );
    }

    protected function addSortDesc(Builder $builder)
    {
        $builder->macro(
            'sortDesc',
            function (Builder $builder) {
                return $builder->withoutGlobalScope($this)->latest($this->sort_field);
            }
        );
    }

    protected function addFixSort(Builder $builder)
    {
        $builder->macro(
            'fixSort',
            function (Builder $builder, array $ids = []) {
                $items = $builder->oldest()->get();
                return $items->unless(
                    empty($ids),
                    function ($collection) use ($ids) {
                        return $collection->sortBy(
                            function ($item) use ($ids) {
                                $key = array_search($item->id, $ids);
                                return $key === false ? 9999 : $key;
                            }
                        );
                    }
                )
                ->values()
                ->map(
                    function ($item, $key) {
                        $item->setSortNum($key + 1);
                        return $item;
                    }
                )
                ->sortBy(
                    function ($item) {
                        return $item[$this->sort_field];
                    }
                );
            }
        );
    }
}
