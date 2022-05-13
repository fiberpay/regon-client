<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;

class RegonServiceCallFailedException extends Exception
{

    /**
     * @param string $message
     * @param int|mixed $code
     * @param Exception|null $previous
     */
    public function __construct(string $message, $code = null, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
