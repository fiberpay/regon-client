<?php

namespace Fiberpay\RegonClient;

use Fiberpay\RegonClient\Exceptions\RegonServiceCallFailedException;
use SimpleXMLElement;

final class RegonSearchResponseParser
{
    /**
     * Parse a response returned by DaneSzukajPodmioty.
     *
     * A single record keeps the historical associative-array shape. Multiple
     * records are returned as an ordered list of associative arrays.
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     * @throws RegonServiceCallFailedException
     */
    public function parse(string $xml): array
    {
        return $this->parseDocument($this->parseXml($xml));
    }

    /**
     * Parse an already loaded BIR response document.
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function parseDocument(SimpleXMLElement $response): array
    {
        $records = [];

        foreach ($response->dane as $record) {
            $records[] = json_decode(
                json_encode(get_object_vars($record), JSON_THROW_ON_ERROR),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return match (count($records)) {
            0 => [],
            1 => $records[0],
            default => $records,
        };
    }

    /**
     * Parse an XML response without exposing parser warnings or the raw
     * response content to callers.
     *
     * @throws RegonServiceCallFailedException
     */
    public function parseXml(string $xml): SimpleXMLElement
    {
        $previousInternalErrorsState = libxml_use_internal_errors(true);
        $response = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrorsState);

        if ($response === false) {
            throw new RegonServiceCallFailedException('Unable to parse REGON search response.');
        }

        return $response;
    }
}
