<?php

namespace Zeaven\EasySuit\SanctumExtension;

use Arr;
use Illuminate\Http\Request;
use Laravel\Sanctum\Events\TokenAuthenticated;
use Laravel\Sanctum\Guard;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\TransientToken;

class CacheGuard extends Guard
{
    public function __invoke(Request $request)
    {
        foreach (Arr::wrap(config('sanctum.guard', 'web')) as $guard) {
            if ($user = $this->auth->guard($guard)->user()) {
                return $this->supportsTokens($user)
                    ? $user->withAccessToken(new TransientToken())
                    : $user;
            }
        }

        if ($token = $this->getTokenFromRequest($request)) {
            $model = Sanctum::$personalAccessTokenModel;

            $accessToken = $model::findToken($token);

            $userProvider = $this->getUserProvider();
            if ($userProvider) {
                $accessToken->setRelation('tokenable', $userProvider->retrieveById($accessToken->tokenable_id));
            }

            if (
                ! $this->isValidAccessToken($accessToken) ||
                ! $this->supportsTokens($accessToken->tokenable)
            ) {
                return;
            }

            $tokenable = $accessToken->tokenable->withAccessToken(
                $accessToken
            );

            event(new TokenAuthenticated($accessToken));

            if (config('sanctum.update_last_used_at')) {
                if (
                    method_exists($accessToken->getConnection(), 'hasModifiedRecords') &&
                    method_exists($accessToken->getConnection(), 'setRecordModificationState')
                ) {
                    tap($accessToken->getConnection()->hasModifiedRecords(), function ($hasModifiedRecords) use ($accessToken) {
                        $accessToken->forceFill(['last_used_at' => now()])->save();

                        $accessToken->getConnection()->setRecordModificationState($hasModifiedRecords);
                    });
                } else {
                    $accessToken->forceFill(['last_used_at' => now()])->save();
                }
            }

            return $tokenable;
        }
    }

    protected function getUserProvider()
    {
        $guard = Arr::first(config('sanctum.guard', []), null, $this->auth->getDefaultDriver());

        $guardConfig = config('auth.guards.' . $guard);

        return $this->auth->createUserProvider($guardConfig['provider']);
    }
}
