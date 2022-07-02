<?php

/**
 * 无限级代理
 * @authors master (master@v8y.com)
 * @date    2021-03-31 09:03:26
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\UnlimitedAgentable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UnlimitedAgentScope implements Scope
{
    public function __construct()
    {
    }

    protected $extensions = [];

    /**
     * 把约束加到 Eloquent 查询构造中。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // $builder->latest();
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
}
