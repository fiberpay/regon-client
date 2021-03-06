<?php

namespace Fiberpay\RegonClient\Exceptions;

use InvalidArgumentException;

class InvalidEntityIdentifierException extends InvalidArgumentException
{

    /**
     * @param $identifierName
     * @param $identifierValue
     */
    public function __construct($identifierName, $identifierValue)
    {
        parent::__construct("$identifierValue is not valid $identifierName");
    }
}
