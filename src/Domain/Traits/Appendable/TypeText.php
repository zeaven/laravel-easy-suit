<?php

/**
 * 类名必须符合规则：{表名}{操作}Trait，如标签管理=TagMgrTrait，课件查询=coursewareQueryTrait
 * 禁止如下命名：TagTrait，TagUpdateCreateTrait
 * 方法参数包涵数组的，必须标明数据包括的属性
 * create time: 2019-12-13 11:46:55
 * create by:
 */

namespace Zeaven\EasySuit\Domain\Traits\Appendable;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * 针对性别字段做转换
 * const GENDERS = ['未知', '男', '女'] // 定义转换内容
 */
trait TypeText
{
    protected function initializeTypeText()
    {
        $this->append('type_text');
    }

    private function getTypes()
    {
        return defined('static::TYPES') ? static::TYPES : [];
    }

    private function getTypesMin()
    {
        return defined('static::TYPES_MIN') ? static::TYPES_MIN : 0;
    }

    protected function typeText(): Attribute
    {
        $min = $this->getTypesMin();
        $types = $this->getTypes();
        return new Attribute(
            get: fn ($_, $attributes) => $types[intval($attributes['type'] ?? -99) - $min] ?? '',
            set: fn ($value) => ['type' => $min + (array_search($value, $types) ?: 0)],
        );
    }
}
