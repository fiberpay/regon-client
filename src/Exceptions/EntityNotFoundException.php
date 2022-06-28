<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;
use SimpleXMLElement;

class EntityNotFoundException extends Exception
{

    /**
     * @param false|SimpleXMLElement $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
