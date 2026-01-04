<?php

namespace Zeaven\EasySuit\SanctumExtension;

use Laravel\Sanctum\Guard;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\TransientToken;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Events\TokenAuthenticated;

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

            if ($accessToken != null) {
                $accessToken->setRelation('tokenable', $this->getUser($accessToken));
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

    protected function getUser(PersonalAccessToken $accessToken)
    {

        $authConfig = config('auth');

        foreach (Arr::wrap(config('sanctum.guard', ['web'])) as $guard) {
            $guardConfig = Arr::get($authConfig, 'guards.' . $guard);
            $providerConfig = Arr::get($authConfig, 'providers.' . $guardConfig['provider']);
            if ($providerConfig) {
                $model = $providerConfig['model'];

                if (Str::contains($model, $accessToken->tokenable_type)) {
                    $provider = Auth::createUserProvider($guardConfig['provider']);
                    $user = $provider->retrieveById($accessToken->tokenable_id);
                    if ($user) {
                        return $user;
                    }
                }
            }
        }

        return $accessToken->tokenable;
    }
}
