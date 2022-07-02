<?php

/**
 * 参数基类
 *
 * @date    2020-06-05 15:51:26
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Core;

use ArrayAccess;
use JsonSerializable;

abstract class DomainParam implements
    ArrayAccess,
    JsonSerializable
{
    protected $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->fill($attributes);
    }

    protected function fill(array $attributes)
    {
        foreach ($attributes as $attr => $value) {
            if (property_exists($this, $attr)) {
                $this->{$attr} = $value;
            }
        }
    }

    protected function getAttribute($key)
    {
        if (!$key || !isset($this->attributes[$key])) {
            return;
        }
        return $this->attributes[$key];
    }

    protected function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (property_exists($this, $offset)) {
            return ! is_null($this->{$offset});
        } else {
            return ! is_null($this->getAttribute($offset));
        }
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (property_exists($this, $offset)) {
            return $this->{$offset};
        }
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        } else {
            $this->setAttribute($offset, $value);
        }
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset)) {
            $this->{$offset} = null;
        }
        unset($this->attributes[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    public function toArray(): array
    {
        $vars = get_object_vars($this);
        unset($vars['attributes']);
        return $vars + $this->attributes;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
