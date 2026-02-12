<?php

namespace Fiberpay\RegonClient\Exceptions;

use Exception;
use Fiberpay\RegonClient\RegonErrorCode;

/**
 * Thrown when the entity exists in REGON but the requested data is not available.
 *
 * Examples:
 * - PKD codes not available for entities deleted before 2014-11-08 (code 11)
 * - Entity is not a civil partnership, so partnership report is not applicable (code 21)
 * - No partners registered for this civil partnership (code 22)
 *
 * Extends EntityNotFoundException for backward compatibility — existing catch blocks
 * that catch EntityNotFoundException will still work.
 */
class RegonDataNotAvailableException extends Exception implements RegonApiErrorResponseException
{
    private RegonErrorCode $errorCode;

    public function __construct(string $message, RegonErrorCode $errorCode)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): RegonErrorCode
    {
        return $this->errorCode;
    }
}
