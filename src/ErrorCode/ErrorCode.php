<?php

/**
 * 错误码
 *
 * @date    2018-10-31 17:54:51
 * @version $Id$
 */

namespace Zeaven\EasySuit\ErrorCode;

use Illuminate\Support\Str;

class ErrorCode
{
    private $data = [];
    public function get(int $code, string $msg = null)
    {
        if ($msg) {
            return $msg ? __($msg) : '';
        }

        if (is_numeric($code) && $code > 1000) {
            $code = dechex($code);
        }

        $codeStr = strtolower(Str::after($code, '0x'));
        $errData = request()->errorData() ?: [];

        return __(ErrorCodeProvider::$abstract . ".{$codeStr}", $errData);
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
