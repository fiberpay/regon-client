<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;
use SimpleXMLElement;
use Fiberpay\RegonClient\RegonErrorCode;

interface RegonApiErrorResponseException
{
    public function getErrorCode(): RegonErrorCode;
}
