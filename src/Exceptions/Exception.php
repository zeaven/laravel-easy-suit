<?php

namespace Zeaven\EasySuit\Exceptions;

use Exception as BaseException;

class Exception extends BaseException
{
    private $errorCode;
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, int $errorCode = 0)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode ? $errorCode : $code;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
