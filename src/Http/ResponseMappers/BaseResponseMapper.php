<?php

namespace Zeaven\EasySuit\Http\ResponseMappers;

use Arr;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderTapProxy;

/**
 * 响应资源属性映射
 *
 * 使用说明：
 *
 * 属性映射
 * 'username' => 'user.0.username', // 将user对象数组的第一个对象username映射到顶级username字段
 * 'bc' => 'bank_card.*',   // 将bank_card记录的所有字段展开，并添加前缀'bc_',如果前缀是'_'，则不加前缀
 * // 对user数组的每个对象内部做字段映射
 * 'user.*' => [
 *      'name' => 'username',
 *      'status' => 'status_text'
 *  ]
 *  // 将字段'user.0.role.name' 的值经过多个handle转换后映射到顶级role字段
 * 'role' => ['user.0.role.name', RoleHandler::class, ...] // 通过RoleHandler类进行转换，可填多个
 *
 * RoleHandler::class 说明：
 * class RoleHandler
 * {
 *     // $value为键：user.0.role.name对应值/或上一个handler返回值， $data为当前记录user.0.role的值
 *     public function handle($value, $data)
 *     {
 *         return $value. $data['description'];
 *     }
 * }
 * @date    2020-07-10 09:25:00
 * @version $Id$
 */
abstract class BaseResponseMapper implements Arrayable
{
    protected static $handlerInstances = [];
    private $_value;
    /**
     * 属性映射配置
     * @var array
     */
    protected $mapper = [];

    /**
     * 隐藏属性配置
     * @var array
     */
    protected $hidden = [];

    public function __construct($value)
    {
        $this->_value = $value instanceof HigherOrderTapProxy ? $value->target : $value;
    }

    public function isArray()
    {
        return ($this->_value instanceof Collection) || is_array($this->_value);
    }

    protected static function getHandler(string $handle_class)
    {
        if (array_key_exists($handle_class, static::$handlerInstances)) {
            return static::$handlerInstances[$handle_class];
        }

        return static::$handlerInstances[$handle_class] = app($handle_class);
    }

    public function toArray()
    {
        $data = $this->_value;
        if (!is_array($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }
        $mappers = $this->mapper ?? [];
        $this->convertData($data, $mappers);
        $hiddens = $this->hidden ?? [];
        $this->hiddenData($data, $hiddens);

        return $data;
    }

    protected function hiddenData(&$data, array $hiddens)
    {
        foreach ($hiddens as $hidden) {
            $keys = explode('.', $hidden);
            $this->setHidden($data, $keys);
        }
    }

    protected function convertData(&$data, array $mappers)
    {
        foreach ($mappers as $key => $mapper) {
            $keys = explode('.', $key);
            $this->convertAttr($data, $keys, $mapper);
        }
    }

    /**
     * 转换属性
     * @param  [type] &$data  [description]
     * @param  array  $keys   [description]
     * @param  mxied $mapper [description]
     * @return [type]         [description]
     */
    protected function convertAttr(&$data, array $keys, $mapper)
    {
        $key = array_shift($keys);
        if ($key === '*') {
            throw_on(!is_array($mapper), '转换属性为数组时，值必须是数组');
            // 当前$data为数组
            foreach ($data as &$item) {
                $this->convertData($item, $mapper);
            }
            return;
        }
        if (empty($keys)) {
            if (is_array($mapper)) {
                // 转换属性为数组时，第一个值为key，第二个值为转换类，如 ['username' => ['user.name', Name::class]]
                // Name::class 将处理转换的数据
                $clazz = $mapper;
                $mapper = array_shift($clazz);
            }
            // 已是最后一个key
            [$spread, $value] = $this->getKeyValue($data, $mapper);
            if (isset($clazz)) {
                foreach ($clazz as $class) {
                    if (is_callable($class)) {
                        $value = $class($value, $data);
                    } else {
                        $handler = static::getHandler($class);
                        $value = $handler->handle($value, $data);
                    }
                }
            }
            if ($spread) {
                foreach ($value as $k => $v) {
                    $data[ltrim("{$key}_{$k}", '_')] = $v;
                }
            } else {
                $data[$key] = $value;
            }
            return;
        }

        $data = &$data[$key];
        if (empty($data)) {
            return;
        }
        if (method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }
        $this->convertAttr($data, $keys, $mapper);
    }

    protected function getKeyValue($data, $key)
    {
        $keys = explode('.', $key);
        do {
            $key = array_shift($keys);
            if ($key === '*') {
                return [true, $data];
            }
            $data = &$data[$key];
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            }
        } while ($data && $keys);
        return [false, $data];
    }

    protected function setHidden(&$data, array $keys)
    {
        $key = array_shift($keys);
        if (empty($keys)) {
            unset($data[$key]);
        } else {
            if ($key === '*') {
                foreach ($data as &$item) {
                    $this->setHidden($item, $keys);
                }
            } else {
                $data = &$data[$key];
                if (method_exists($data, 'toArray')) {
                    $data = $data->toArray();
                }
                $this->setHidden($data, $keys);
            }
        }
    }

    public static function _($value)
    {
        return new static($value);
    }
}
