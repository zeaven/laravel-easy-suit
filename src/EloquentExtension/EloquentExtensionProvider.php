<?php

namespace Zeaven\EasySuit\EloquentExtension;

use Zeaven\EasySuit\EloquentExtension\SlimLengthAwarePaginator;
use Closure;
use Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

// use Kalnoy\Nestedset\Collection as NestedCollection;

class EloquentExtensionProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }


    private function eloquentBuildExtends()
    {
        if (!config('easy_suit.model.extension')) {
            return;
        }
        MorphTo::macro('_select', function ($columns) {
            $columns = is_array($columns) ? $columns : func_get_args();
            $this->macroBuffer[] = ['method' => 'select', 'parameters' => $columns];
            return $this;
        });

        Builder::macro('with_columns', function (string $relation, array $keys, Closure $callback = null) {
            return $this->with([
                "{$relation}" => function ($query) use ($keys, $callback, $relation) {
                    if (!empty($keys)) {
                        if ($query instanceof MorphTo) {
                            $table_name = false;
                        } else {
                            $table_name = $query->getRelated()->getTable();
                        }
                        if (is_string($table_name)) {
                            for ($i = 0, $l = count($keys); $i < $l; $i++) {
                                (stripos($keys[$i], '.') === false) and ($keys[$i] = "{$table_name}.{$keys[$i]}");
                            }
                            $query->select($keys);
                        } else {
                            $query->_select($keys);
                        }
                    }
                    if (is_callable($callback)) {
                        $callback($query);
                    }
                }
            ]);
        });

        /**
         * $query->withs('table1:col1,col2', 'table2:col1,col2'); table2 belong to table1
         * $query->withs([
         *     'table1:col1,col2',
         *     'table2:col1,col2' => function($query) {},
         *     'table3:col1,col2'
         *    ])
         */
        Builder::macro('withs', function ($relations) {
            $relations = is_array($relations) ? $relations : func_get_args();
            $relation_define = head(array_keys($relations));
            if (is_string($relation_define)) {
                $relation = $relation_define;
                $callback = array_shift($relations);
            } else {
                $relation = array_shift($relations);
                $callback = null;
            }
            list($table, $columns) = stripos($relation, ':') ? explode(':', $relation) : [$relation, ''];
            $columns = empty($columns) ? [] : array_map('trim', explode(',', $columns));
            $this->with_columns($table, $columns, function ($query) use ($relations, $callback) {
                if (is_callable($callback)) {
                    $callback($query);
                }
                if (!empty($relations)) {
                    $query->withs($relations);
                }
            });

            return $this;
        });

        Builder::macro('selectWhen', function ($select, $default = ['*']) {
            $select = is_array($select) ? $select : func_get_args();
            $select = array_filter($select);
            if (!empty($select)) {
                $this->select($select);
            } elseif ($default) {
                $this->select($default);
            }

            return $this;
        });


        Builder::macro('whereWhen', function (...$args) {
            $val = last($args);
            if (filled($val)) {
                $this->where(...$args);
            }
            return $this;
        });


        Builder::macro('filledWhen', function () {
            $filleds = func_get_args();
            $callback = array_pop($filleds);
            $is_filled = !empty(array_filter($filleds, fn ($item) => filled($item)));

            $this->when(
                $is_filled,
                function ($query) use ($filleds, $callback) {
                    $callback && $callback($query, ...$filleds);
                }
            );

            return $this;
        });

        Builder::macro('betweenWhen', function ($column, $first, $second) {
            $this->when(filled($first) || filled($second), function ($query) use ($column, $first, $second) {
                if ($first && $second) {
                    $query->whereBetween($column, [$first, $second]);
                } elseif ($first) {
                    $query->where($column, '>=', $first);
                } else {
                    $query->where($column, '<=', $second);
                }
            });
            return $this;
        });

        Builder::macro('likeWhen', function ($column, $value) {
            $this->when(filled($value), function ($query) use ($column, $value) {
                $query->where($column, 'like', "%{$value}%");
            });
            return $this;
        });


        /**
         * $babys->pluckByKeys(['class_name' => 'class.class_name', 'name'=>'username', 'baby']);
         * 返回[
         *  [
         *      'class_name' => 'xxx', 班级名称
         *      'name' => 'yyy',  宝贝名称
         *      'baby' => [
         *          ...宝贝记录
         *      ]
         * ]
         */
        Collection::macro('pluckByKeys', function ($columns) {
            return $this->map(function ($item) use ($columns) {
                $result = [];
                foreach ($columns as $field => $key) {
                    if (is_numeric($field)) {
                        $field = $key;
                        $key = null;
                    }
                    $result[$field] = data_get($item, $key);
                }

                return $result;
            });
        });
    }

    private function eloquentPagination()
    {
        $pageConfig = config('easy_suit.model.simple_pagination');
        if ($pageConfig) {
            // 自定义分页，将分页属性 data 改为 items
            $this->app->bind(
                LengthAwarePaginator::class,
                function ($_, $arguments) use ($pageConfig) {
                    extract($arguments);
                    if (is_string($pageConfig) && class_exists($pageConfig)) {
                        return new $pageConfig($items, $total, $perPage, $currentPage, $options);
                    } else {
                        return new SlimLengthAwarePaginator($items, $total, $perPage, $currentPage, $options);
                    }
                }
            );
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->eloquentBuildExtends();

        $this->eloquentPagination();
    }
}
