<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;
use Fiberpay\RegonClient\RegonErrorCode;

class EntityNotFoundException extends Exception implements RegonApiErrorResponseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): RegonErrorCode
    {
        return RegonErrorCode::ENTITY_NOT_FOUND;
    }
}
