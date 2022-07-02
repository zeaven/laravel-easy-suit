<?php

/**
 * error code 门面
 *
 * @date    2018-10-31 17:52:04
 * @version $Id$
 */

namespace Zeaven\EasySuit\ErrorCode;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return ErrorCodeProvider::$abstract;
    }
}
