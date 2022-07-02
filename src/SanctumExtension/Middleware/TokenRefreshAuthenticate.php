<?php

namespace Zeaven\EasySuit\SanctumExtension\Middleware;

use Closure;
use DB;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

class TokenRefreshAuthenticate extends Authenticate
{


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $newAccessToken = $this->authenticate($request, $guards);
        $response = $next($request);

        if ($newAccessToken) {
            // Send the refreshed token back to the client.
            $response->headers->set('Authorization', 'Bearer ' . $newAccessToken->plainTextToken);
        }

        return $response;
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
                $newAccessToken = $this->refreshToken($this->auth->user());

                return $newAccessToken;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    private function refreshToken(Model $user)
    {
        $currentAccessToken = $user->currentAccessToken();
        if ($currentAccessToken instanceof TransientToken) {
            // 同源策略，将从cookie和session中读取用户信息，使用xsrfToken保护
            return;
        }
        // 第三方请求将定期刷新token，防止token泄漏造成安全问题
        $config = config('sanctum');
        $refreshTTL = $config['refresh_ttl'];
        $removeToken = $config['remove_refresh_expire_token'];
        $refreshGraceTTL = $config['refresh_grace_ttl'] ?: 3;
        if (!$refreshTTL) {
            return;
        }

        return cache()->lock($currentAccessToken->plainTextToken, $refreshGraceTTL)
            ->block(
                1,
                function () use ($currentAccessToken, $refreshTTL, $removeToken) {
                    if ($currentAccessToken->created_at->lt(now()->subMinutes($refreshTTL))) {
                        // 过期刷新
                        return DB::transaction(function () use ($currentAccessToken, $removeToken) {
                            $newAccessToken = $currentAccessToken->tokenable->createToken($currentAccessToken->name, $currentAccessToken->abilities);
                            auth()->guard(config('sanctum.guard')[0])->login($currentAccessToken->tokenable);
                            $removeToken && $currentAccessToken->delete();     // 自行判断是否删除

                            return $newAccessToken;
                        });
                    }
                }
            );
    }
}
