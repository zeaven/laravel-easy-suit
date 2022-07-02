<?php

/**
 *
 *
 * @date    2019-09-24 12:38:12
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Locational;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use DB;

class LocationalScope implements Scope
{
    protected $extensions = ['whereProvince', 'whereCity', 'whereArea', 'whereTown', 'anyLocation'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('code', 'like', '__0000')->select(['code','name']);
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

    protected function addWhereProvince(Builder $builder)
    {
        $builder->macro('whereProvince', function (Builder $builder, $province = null) {
            $builder->withoutGlobalScope($this)->when(
                $province,
                function ($query, $province) {
                    $query->whereCode($province);
                },
                function ($query) {
                    $query->where('code', 'like', '__0000');
                }
            )->select(['code','name']);
            return $builder;
        });
    }

    protected function addWhereCity(Builder $builder)
    {
        $builder->macro('whereCity', function (Builder $builder, $province = null) {
            $builder->withoutGlobalScope($this)->when(
                $province,
                function ($query, $province) {
                    $query->where('code', '<>', $province)
                        ->where('code', 'like', substr($province, 0, 2) . '__00');
                },
                function ($query) {
                    $query->where('code', 'like', '____00');
                }
            )->select(['code','name']);
            return $builder;
        });
    }

    protected function addWhereArea(Builder $builder)
    {
        $builder->macro('whereArea', function (Builder $builder, $city = null) {
            $builder->withoutGlobalScope($this)->when(
                $city,
                function ($query, $city) {
                    $query->where('code', '<>', $city)
                        ->where('code', 'like', substr($city, 0, 4) . '__');
                },
                function ($query) {
                    $query->where('code', 'not like', '%00');
                }
            )->select(['code','name']);
            return $builder;
        });
    }

    protected function addWhereTown(Builder $builder)
    {
        $builder->macro('whereTown', function (Builder $builder, $area) {
            return DB::table('location_streets')->where('code', 'like', "{$area}%")->select(['code','name']);
        });
    }

    protected function addAnyLocation(Builder $builder)
    {
        $builder->macro('anyLocation', function (Builder $builder) {
            $builder->withoutGlobalScope($this);
            return $builder;
        });
    }
}
