<?php

namespace Zeaven\EasySuit\Annotations;

interface AnnoLogHandler
{
    public function handler(string $message, array $data);
}