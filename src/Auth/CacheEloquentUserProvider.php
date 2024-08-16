<?php

/**
 * 自定义用户提供者
 * 将缓存用户信息一小时，可减少请求查询用户表信息
 * CustomEloquentUserProvider::refresh(uid) 将清除缓存信息
 *
 * @date    2019-08-13 19:41:48
 * @version $Id$
 */

namespace Zeaven\EasySuit\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

/**
 * 账号用户关系模型如：
 * Account<---N:1-->User
 * Account和User都有uid字段
 * 登录时调用retrieveByCredentials查询Account，并且返回的是User
 * 请求接口时通过uid调用retrieveById查询用户表
 * 此时可从User表查询
 * 这样auth()->user()获取的就是user表
 */
class CacheEloquentUserProvider extends EloquentUserProvider
{
    // 缓存Account账号信息时间
    const CACHE_SECOND = 3600;

    // 字段数组、账户模型
    protected array $fields = [];
    protected static $authModel;

    /**
     * CustomEloquentUserProvider constructor.
     * @param HasherContract $hasher
     * @param $model
     * @param $authModel
     * @param array $fields
     */
    public function __construct(HasherContract $hasher, $model, $authModel, $fields = [])
    {
        // 父类的构造函数初始化从父类继承的成员数据
        parent::__construct($hasher, $model);

        // 初始化子类定义的成员数据
        $this->fields = $fields ?? [];
        static::$authModel = $authModel;
    }

    /**
     * 缓存数据
     * @param string $key 主键
     * @param callable $callback 回调函数
     * @return mixed
     * @throws \Exception
     */
    private static function cache(string $key, callable $callback)
    {
        return cache()->tags(['auth'])->remember(
            class_basename(static::$authModel) . ':' . $key,
            static::CACHE_SECOND,
            $callback
        );
    }

    /**
     * 删除登录缓存
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public static function refresh(string $key)
    {
        return cache()->tags(['auth'])->forget(class_basename(static::$authModel) . ':' . $key);
    }

    /**
     * 查找登录缓存
     * @param string $key
     * @return bool
     * @throws \Exception
     */
    private function has(string $key): bool
    {
        return cache()->tags(['auth'])->has(class_basename(static::$authModel) . ':' . $key);
    }

    /**
     * 通过主键获取用户信息
     * @param mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function retrieveById($identifier)
    {
        // 获取用户数据，先从缓存查，没有再去数据库查询
        $user = $this->cache(
            $identifier,
            function () use ($identifier) {
                $model = $this->createModel();
                $user = $this->newModelQuery($model)
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->selectWhen($this->fields)
                    ->first();

                // throw_empty($user, 0xf00012);
                // throw_on($user->status === -1, 0xf00242);
                // $user->setHidden(['gender_text', 'password']);
                return $user?->toArray();
            }
        );

        // 使用用户数据创建用户实例
        return $this->createCacheModel($user);
    }

    /**
     * 使用传入的数据创建用户实例
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createCacheModel(?array $attributes)
    {
        if (empty($attributes)) {
            return;
        }
        $model = $this->createModel();
        $model->fill($attributes);
        $model->id = $attributes['id'];
        $model->exists = true;

        return $model;
    }

    /**
     * 记住token
     * @param mixed $identifier
     * @param string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->cache(
            $identifier,
            function () use ($identifier, $token) {

                $model = $this->createModel();
                $user = $this->newModelQuery($model)
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->selectWhen($this->fields)
                    ->first();

                if (filled($user) && $this->model !== static::$authModel) {
                    $relation = strtolower(class_basename(static::$authModel));
                    $user->load($relation);
                    $authData = $user->{$relation};
                    $rememberToken = $authData->getRememberToken();

                    return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
                }
                // throw_empty($user, 0xf00012);
                // throw_on($user->status === -1, 0xf00242);
                // $user->setHidden(['gender_text', 'password']);
                return $user?->toArray();
            }
        );

        return $this->createCacheModel($user);
    }

    /**
     * @param array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function retrieveByCredentials(array $credentials)
    {
        if ($this->model === static::$authModel) {
            return parent::retrieveByCredentials($credentials);
        }
        // 切换到验证模型，即Account表
        $userModel = $this->model;
        $this->model = static::$authModel;
        $authData = throw_empty(parent::retrieveByCredentials($credentials), 0xf00012);

        // 切换回来
        $this->model = $userModel;
        $key = $this->createModel()->getAuthIdentifierName();
        // 账户对应实体表(user, admin)
        $relation = strtolower(class_basename($this->model));
        if (empty($this->fields)) {
            $authData->load($relation);
        } else {
            $fields = implode(',', $this->fields);
            $authData->load("{$relation}:{$fields}");
        }

        static::refresh($authData->{$relation}->{$key});
        throw_empty(method_exists($authData, $relation), 0xf00012); // 账号不存在对应实体

        // $model->$relation = $this->retrieveById($model->{$relation}->{$key});
        // throw_on(!empty($model->$relation->disabled_at), 0xf00242);
        // 提供 实体 模型验证密码功能
        $authData->$relation->password = $authData->password;
        // $model->$relation->setHidden(['gender_text', 'password']);

        return $authData->$relation;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
