<?php

namespace Zeaven\EasySuit\SanctumExtension\Listeners;

use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Events\TokenAuthenticated;

class TokenAuthenticatedListener
{
    /**
     * 处理事件
     *
     * @param  \App\Events\OrderShipped  $event
     * @return void
     */
    public function handle(TokenAuthenticated $event)
    {
        $userProvider = Auth::getProvider();
        if (method_exists($userProvider, 'getFields')) {
            $fields = $userProvider->getFields();
            $user = $event->token->tokenable;
            $user->setVisible($fields);
        }
    }
}
