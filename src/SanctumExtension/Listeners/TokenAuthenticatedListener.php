<?php

namespace Zeaven\EasySuit\SanctumExtension\Listeners;

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
        $userProvider = auth()->guard()->getProvider();
        if (method_exists($userProvider, 'getFields')) {
            $fields = $userProvider->getFields();
            $user = $event->token->tokenable;
            $user->setVisible($fields);
        }
    }
}
