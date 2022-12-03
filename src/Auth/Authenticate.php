<?php

namespace Zeaven\EasySuit\Auth;

use App\Http\Middleware\Authenticate as Middleware;
use Arr;
use Closure;
use Zeaven\EasySuit\Jwt\AutoRefreshJwtAuth;
use Zeaven\EasySuit\SanctumExtension\Middleware\TokenRefreshAuthenticate;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (count($guards)) {
            $authConfig = config('easy_suit.auth');
            if ($guards[0] === 'sanctum' && Arr::get($authConfig, 'sanctum', false) {
                return (new TokenRefreshAuthenticate($this->auth))->handle($request, $next, 'sanctum');
            } else if ($guards[0] === 'jwt' && Arr::get($authConfig, 'jwt.enable', false)) {
                return (new AutoRefreshJwtAuth($this->auth))->handle($request, $next, 'jwt');
            }
        }
        return parent::handle($request, $next, ...$guards);
    }
}
