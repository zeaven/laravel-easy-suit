<?php

/**
 * 领域服务基础
 *
 * @date    2020-06-23 10:42:57
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Core;

use Zeaven\EasySuit\Domain\Core\Model;
use Str;

abstract class DomainService
{
    protected $config = [];
    protected $ctx;
    protected static $booteds = [];

    public function __construct()
    {
        // 1) 读取配置
        if (defined('static::CONFIG') && static::CONFIG !== '') {
            $this->config = config('common.domain.' . static::CONFIG);
        }

        // 2) 初始化上下文
        if (defined('static::CONTEXT') && static::CONTEXT !== '') {
            $ctx = static::CONTEXT;
            $this->ctx = new $ctx();
        }

        // 3) 初始化回调
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }

        // 4) 仅执行一次 booted()
        if (!isset(static::$booteds[static::class])) {
            static::$booteds[static::class] = true;
            static::booted();
        }
    }

    protected static function booted()
    {
    }

    public function setUser(Model $user)
    {
        if ($this->ctx) {
            $this->ctx->setUser($user);
        }
        return $this;
    }

    public function __get($entity)
    {
        if ($entity === 'user' && $this->ctx) {
            return $this->ctx->user;
        }
        $entityName = Str::studly($entity);
        $entityClass =  str_replace('Service\\' . class_basename(static::class), 'Entity\\', static::class) . $entityName;

        throw_on(!class_exists($entityClass), 'Entity not exists!');

        return resolve($entityClass);
    }
}
