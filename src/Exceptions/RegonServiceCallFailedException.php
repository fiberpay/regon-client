<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;

class RegonServiceCallFailedException extends Exception
{
    public function __construct(string $message, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
