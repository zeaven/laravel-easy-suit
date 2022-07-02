<?php

/**
 * 排序特性
 * sort_num 排序字段说明：
 * 使用间隔为1000，小数点3位，如：
 * A = 1000.000,
 * B = 2000.000,
 * C = 3000.000,
 * D = 4000.000
 * 如果将 B移动A,则 B=(A+A-1000)/2, 即:
 * B = 500.000,
 * A = 1000.000,
 * C = 3000.000,
 * D = 4000.000
 * 如果将 A移动D,则 A=(D+D+1000)/2, 即：
 * B = 500.000,
 * C = 3000.000,
 * D = 4000.000,
 * A = 4500.000
 * 如果将 D移动C,则 D=(B+C)/2, 即：
 * B = 500.000,
 * D = 1750.000,
 * C = 3000.000,
 * A = 4500.000
 * 所以计算公式为，将当前项X移动Y、Z之间，则X的值=(Y+Z)/2，如Y不存在（顶部）则Y=Z-1000，，如Z不存在（底部）则Z=Y+1000
 *
 * @date    2019-09-16 15:01:37
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Sortable;

use Zeaven\EasySuit\Domain\Traits\Sortable\SortableScope;

/**
 * 提供模型排序功能
 * static::SORT_NUM 为排序字段，float型，保留3位小数
 */
trait Sortable
{
    private static $maxSortNum = false;
    public static function bootSortable()
    {
        // 添加查询作用域
        static::addGlobalScope(new SortableScope(static::getSortNumColumn()));

        static::creating(
            function ($model) {
                static::getMaxSortNum();
                $model->calcMaxSortNum();
            }
        );
    }

    public static function getSortNumColumn()
    {
        return defined('static::SORT_NUM') ? static::SORT_NUM : 'sort_num';
    }

    public static function getSortStepValue()
    {
        return defined('static::SORT_STEP') ? static::SORT_STEP : 1000;
    }

    /**
     * 计算排序值
     * @return [type] [description]
     */
    private function calcMaxSortNum()
    {
        $step = static::getSortStepValue();
        $column = static::getSortNumColumn();

        $this->attributes[$column] = static::$maxSortNum += $step;
    }

    private static function getMaxSortNum()
    {
        // 取得最大字段
        if (static::$maxSortNum === false) {
            static::$maxSortNum = static::max(static::getSortNumColumn()) ?: 0;
        }
        return static::$maxSortNum;
    }

    public function setSortNum(int $num)
    {
        $step = static::getSortStepValue();
        $column = static::getSortNumColumn();
        $this->setAttribute($column, $num * $step);
        if (static::$maxSortNum === false || static::$maxSortNum < $this->attributes[$column]) {
            static::$maxSortNum = $this->attributes[$column];
        }
        $this->save();
        return $this;
    }

    /**
     * 默认为倒序，上移即增加
     * @param  int    $step 增加位移数量
     * @return [type]       [description]
     */
    public function up(int $step)
    {
        if ($step < 0) {
            throw new \Exception('argument step is not allow!');
        }
        if ($step === 0) {
            return;
        }
        static::getMaxSortNum();
        $column = static::getSortNumColumn();
        $rows = $this->oldest($column)
            ->where($column, '>=', $this->attributes[$column])
            ->offset($step)->limit(2)->get(['sort_num']);

        switch (count($rows)) {
            case 2:
                $value = floatval(bcdiv(bcadd($rows[0]->sort_num, $rows[1]->sort_num, 3), 2, 3));
                break;
            case 1:
                $value = floatval(bcadd($rows[0]->sort_num, static::getSortStepValue(), 3));
                static::$maxSortNum = $value;
                break;
            default:
                $value = floatval(bcadd(static::$maxSortNum, static::getSortStepValue(), 3));
                static::$maxSortNum = $value;
                break;
        }

        $this->attributes[$column] = $value;
        $this->save();
        return $this;
    }

    public function down(int $step)
    {
        if ($step < 0) {
            throw new \Exception('argument step is not allow!');
        }
        if ($step === 0) {
            return;
        }
        static::getMaxSortNum();
        $column = static::getSortNumColumn();
        $rows = $this->latest($column)
            ->where($column, '<=', $this->attributes[$column])
            ->offset($step)->limit(2)->get(['sort_num']);

        switch (count($rows)) {
            case 2:
                $value = floatval(bcdiv(bcadd($rows[0]->sort_num, $rows[1]->sort_num, 3), 2, 3));
                break;
            case 1:
                $value = floatval(bcdiv($rows[0]->sort_num, 2, 3));
                break;
            default:
                $min_value = $this->min($column) ?: static::getSortStepValue() * 2;
                if ($min_value === $this->attributes[$column]) {
                    $value = $min_value;
                } else {
                    $value = floatval(bcdiv($min_value, 2, 3));
                }
                break;
        }

        $this->attributes[$column] = $value;
        $this->save();
        return $this;
    }
}
