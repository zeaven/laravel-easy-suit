<?php

namespace Zeaven\EasySuit\Domain\Traits\UnlimitedAgentable;

use Zeaven\EasySuit\Domain\Traits\UnlimitedAgentable\UnlimitedAgentScope;

/**
 * 无限级代理
 */
trait UnlimitedAgentable
{
    private static $_UNLIMITED_MIDDLE_TABLE = '';
    private static $_UNLIMITED_CHILD_KEY = 'child_id';
    private static $_UNLIMITED_PARENT_KEY = 'parent_id';
    // 是否需要统计
    private static $_UNLIMITED_STAT = true;

    public static function bootUnlimitedAgentable()
    {
        if (defined('static::UNLIMITED_MIDDLE_TABLE')) {
            static::$_UNLIMITED_MIDDLE_TABLE = static::UNLIMITED_MIDDLE_TABLE;
        }
        if (defined('static::UNLIMITED_CHILD_KEY')) {
            static::$_UNLIMITED_CHILD_KEY = static::UNLIMITED_CHILD_KEY;
        }
        if (defined('static::UNLIMITED_PARENT_KEY')) {
            static::$_UNLIMITED_PARENT_KEY = static::UNLIMITED_PARENT_KEY;
        }
        if (defined('static::UNLIMITED_STAT')) {
            static::$_UNLIMITED_STAT = static::UNLIMITED_STAT;
        }
        // 添加查询作用域
        // static::addGlobalScope(new UnlimitedAgentScope);
    }

    // 上级代理，顺序为 直系上级代理..中间上级代理..顶级上级代理
    public function allParents()
    {
        return $this->belongsToMany(
            static::class,
            static::$_UNLIMITED_MIDDLE_TABLE,
            static::$_UNLIMITED_CHILD_KEY,
            static::$_UNLIMITED_PARENT_KEY
        )
            ->oldest('step')
            ->withPivot('step')
            ->withTimestamps();
    }

    // 直系代理
    public function parents()
    {
        return $this->allParents()->wherePivot('step', 0)->limit(1);
    }

    // 父级代理，树结构
    public function nextParents()
    {
        return $this->parents()->with('nextParents');
    }

    // 下级代理，顺序为 直系下级..中间下级..最低下级
    public function allChildren()
    {
        return $this->belongsToMany(
            static::class,
            static::$_UNLIMITED_MIDDLE_TABLE,
            static::$_UNLIMITED_PARENT_KEY,
            static::$_UNLIMITED_CHILD_KEY
        )
            ->oldest('step')
            ->withPivot('step')
            ->withTimestamps();
    }

    //
    public function children()
    {
        return $this->allChildren()->wherePivot('step', 0)->limit(1);
    }

    // 下级代理，树结构
    public function nextChildren()
    {
        return $this->children()->with('nextChildren');    // 0为直系代理
    }

    /**
     * 添加上级
     * @param  model|int $parent 上级或id
     * @param bool $single 保留唯一上级
     */
    public function attachParent($parent, bool $single = true)
    {
        is_numeric($parent) && $parent = static::find($parent);
        $parent->loadMissing('allParents');
        return db_trans(
            function () use ($parent, $single) {
                if ($single) {
                    // 移除原来的上级
                    $this->detachParent();
                }
                $level = 0;
                // 将邀请的代理添加为当前用户的上级代理
                $this->allParents()->attach($parent->id, ['step' => $level++]); // 0为直属上级
                if (static::$_UNLIMITED_STAT) {
                    // 更新直属上级的统计数
                    $parent->increment('children_count');
                    $parent->increment('total_children_count');
                }

                // 添加上级的上级代理
                foreach ($parent->allParents as $top) {
                    $this->allParents()->attach($top->id, ['step' => $level++]);
                    if (static::$_UNLIMITED_STAT) {
                        // 上级代理的下级统计
                        $top->increment('total_children_count');
                    }
                }

                return $this;
            }
        );
    }
    /**
     * 移除上级
     * @param  model|int $parent 上级对象或id
     * @return [type]         [description]
     */
    public function detachParent($parent = null)
    {
        is_numeric($parent) && $parent = static::findOrFail($parent);
        return db_trans(
            function () use ($parent) {
                if (!$parent) {
                    $this->allParents()->each(
                        function ($top) {
                            if (static::$_UNLIMITED_STAT) {
                                if (data_get($top, 'pivot.step') === 0) {
                                    $top->decrement('children_count');
                                }
                                $top->decrement('total_children_count');
                            }
                            $top->allChildren()->detach($this->id);
                        }
                    );
                } else {
                    $child = $parent->allChildren()->first();
                    if ($child && $child->id === $this->id) {
                        $parent->allChildren()->detach($this->id);
                        if (static::$_UNLIMITED_STAT) {
                            $parent->decrement('children_count');
                            $parent->decrement('total_children_count');
                        }
                    }
                }
                return $this;
            }
        );
    }
    /**
     * 添加下级
     * @param  model|int $child 下级对象或id
     * @return [type]        [description]
     */
    public function attachChildren($child)
    {
        is_numeric($child) && $child = static::findOrFail($child);
        return $child->attachParent($this);
    }
    /**
     * 移除下级
     * @param  model|int $child 下级对象或id
     * @return [type]        [description]
     */
    public function detachChildren($child = null)
    {
        is_numeric($child) && $child = static::findOrFail($child);
        return $child->detachParent($this);
    }
}
