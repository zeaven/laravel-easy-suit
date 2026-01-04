<?php

namespace Zeaven\EasySuit\Jwt;

use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\PayloadException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;

/**
 * 自定义JWT-auth认证中间件
 */
class AutoRefreshJwtAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, string ...$guards)
    {
        $useGuard = Auth::getDefaultDriver();
        $guards    = empty($guards) ? [Auth::getDefaultDriver()] : $guards;
        try {
            foreach ($guards as $guard) {
                if (! config('auth.guards.' . $guard)) {
                    continue;
                }
                $useGuard = $guard;
                if ($this->setGuard($useGuard, $request)) {
                    break;
                }
            }

            if (!Auth::user()) {
                // TODO: 是否需要把token存入数据库中，从后台失效token，增加从后台查询token的合法性
                throw_e(0xf00012);
                // TODO: 增加对不同身份用户的认证，如管理员和普通用户
            }
        } catch (TokenExpiredException $e) {
            // throw_e(0xf00002);
            // 增加token过期，自动刷新的机制
            Auth::shouldUse($useGuard);
            $refreshToken = $this->getRefreshToken();
            // 设置当前请求的token，否则本次请求无效
            $request->headers->set('Authorization', 'Bearer ' . $refreshToken);
            Auth::setToken($refreshToken);
            Auth::setRequest($request);
            Auth::parseToken()->getPayload();

            $response = $next($request);

            // Send the refreshed token back to the client.
            $response->headers->set('Authorization', 'Bearer ' . $refreshToken);

            return $response;
        } catch (TokenInvalidException | TokenBlacklistedException $e) {
            throw_e(0xf00022);
        } catch (JWTException $e) {
            throw_e(0xf00032);
        }

        return $next($request);
    }

    private function getRefreshToken()
    {
        $token = Auth::getToken()->get();

        return cache()->lock($token, 3)
            ->block(
                3,
                function () use ($token) {
                    $refreshToken = cache("jwt:token_gracelist:{$token}");
                    if ($refreshToken) {
                        return $refreshToken;
                    }

                    try {
                        $refreshToken = Auth::refresh();
                        cache(["jwt:token_gracelist:{$token}" => $refreshToken], 60);
                        // 刷新token后，删除原来的token
                    } catch (JWTException $e) {
                        // sentry($e, compact('token'));
                        throw_e(0xf00002);
                    }

                    return $refreshToken;
                }
            );
    }

    private function setGuard(string $useGuard, $request)
    {
        $auth = auth($useGuard);
        Auth::setRequest($request);
        AUTH::parseToken()->getPayload();

        if ($auth->user()) {
            // 设置当前Guard
            Auth::shouldUse($useGuard);

            return true;
        }
        return false;
    }
}
