<?php
namespace Zeaven\EasySuit\SanctumExtension;

use Laravel\Sanctum\PersonalAccessToken;

class CachePersonalAccessToken extends PersonalAccessToken
{
  protected $table = 'personal_access_tokens';

  // 缓存Token时间
    const CACHE_SECOND = 3600;
    public static function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        $instance = cache()->tags(['auth'])->remember('CachePersonalAccessToken:'.$id, static::CACHE_SECOND, function () use ($id) {
          return static::find($id);
        });
        if ($instance) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }
    }
}
