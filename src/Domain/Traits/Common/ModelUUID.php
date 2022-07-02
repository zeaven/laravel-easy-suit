<?php

/**
 * Model UUID 特性
 * @date    2021-01-22 08:59:33
 * @version $Id$
 */

namespace Zeaven\EasySuit\Domain\Traits\Common;

use Zeaven\EasySuit\Domain\Core\Model;
use Str;
use Hidehalo\Nanoid\Client;

trait ModelUUID
{
    protected static function bootModelUUID()
    {
        static::creating(function (Model &$model) {
            if (empty($model->uid)) {
                // $model->uid = Str::orderedUuid()->toString();
                $client = new Client();

                # default random generator
                $model->uid = $client->formattedId('0123456789abcdefghijklmnopqrstuvwxyz', 21);
                // $model->uid = $client->generateId(21);
            }
        });
    }
}
