<?php

namespace Fiberpay\RegonClient;

/**
 * Error codes returned by the REGON (GUS BIR) SOAP API.
 *
 * These codes appear in <ErrorCode> elements within API responses
 * when the requested operation cannot return normal data.
 */
enum RegonErrorCode: string
{
    /** No entry found for the given search criteria */
    case ENTITY_NOT_FOUND = '4';

    /** Invalid or empty report name, or invalid identifier */
    case INVALID_REPORT_NAME_OR_IDENTIFIER = '5';

    /** PKD activity codes are not available for entities deleted before 2014-11-08 */
    case PKD_NOT_AVAILABLE_FOR_DELETED_ENTITIES = '11';

    /** The entity is not a civil law partnership */
    case NOT_A_CIVIL_PARTNERSHIP = '21';

    /** No partners registered in REGON for this civil law partnership */
    case NO_PARTNERS_FOR_CIVIL_PARTNERSHIP = '22';

    /**
     * Whether this error means the entity itself was not found in the registry.
     */
    public function isEntityNotFound(): bool
    {
        return $this === self::ENTITY_NOT_FOUND;
    }

    /**
     * Whether this error means the entity exists but the requested data is not available
     * (e.g., PKD for old deleted entities, or partnership data for non-partnership entities).
     */
    public function isDataNotAvailable(): bool
    {
        return in_array($this, [
            self::PKD_NOT_AVAILABLE_FOR_DELETED_ENTITIES,
            self::NOT_A_CIVIL_PARTNERSHIP,
            self::NO_PARTNERS_FOR_CIVIL_PARTNERSHIP,
        ]);
    }
}
