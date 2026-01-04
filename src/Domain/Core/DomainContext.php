<?php

/**
 * 领域上下文
 *
 * @date    2020-06-23 11:11:46
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Core;

use Illuminate\Support\Facades\Auth;
use Zeaven\EasySuit\Domain\Core\Model;
use Zeaven\EasySuit\Domain\Core\DomainService;

abstract class DomainContext
{
    protected array $services = [];
    protected static $booted = [];
    protected $domainName;
    protected $contextUser;

    public function __construct()
    {
        $this->domainName = str(get_class($this))->before('Context') . 'Context\\';
        static::bindings($this->services, $this->domainName);
    }

    private static function bindings(array $services, string $domain = '')
    {
        if (isset(static::$booted[static::class])) {
            return;
        }
        static::$booted[static::class] = true;
        $container = app();
        foreach ($services as $key => $class) {
            if (is_numeric($key)) {
                continue;
            }
            // 如果没有使用Octane加速，可改为单例绑定
            if (php_sapi_name() === 'cli') {
                $container->bindIf(
                    $domain . $key,
                    function () use ($class) {
                        return resolve($class);
                    }
                );
            } else {
                $container->scopedIf(
                    $domain . $key,
                    function () use ($class) {
                        return resolve($class);
                    }
                );
            }
        }
    }

    public function __get($service)
    {
        if ($service === 'user') {
            return $this->user();
        }
        $instance = resolve($this->domainName . $service);
        if ($instance instanceof DomainService) {
            $instance->setUser($this->contextUser);
        }

        return $instance;
    }

    /**
     * 设置当前上下文登录用户
     * @return [type] [description]
     */
    public function setUser(Model $user)
    {
        $this->contextUser = $user;
    }

    /**
     * 获取当前上下文登录用户
     */
    public function user(string $uid = null)
    {
        if ($uid && $this->contextUser && $this->contextUser->uid === $uid) {
            return $this->contextUser;
        }
        if ($uid) {
            $guard = Auth::getDefaultDriver();
            $provider = config("auth.guards.{$guard}.provider");
            $model = config("auth.providers.{$provider}.model");
            $this->contextUser = (new $model())->whereUid($uid)->first();
        }
        if ($this->contextUser) {
            return $this->contextUser;
        }
        return $this->contextUser = Auth::user();
    }
}
