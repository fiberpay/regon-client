<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fiberpay\RegonClient\Exceptions\RegonServiceCallFailedException;
use Fiberpay\RegonClient\RegonSearchResponseParser;
use PHPUnit\Framework\TestCase;

final class RegonSearchResponseParserTest extends TestCase
{
    public function testItPreservesTheHistoricalSinglePRecordShape(): void
    {
        $result = $this->parser()->parse($this->fixture('search-single-p.xml'));

        self::assertSame([
            'Regon' => '276854946',
            'Nip' => '1234567890',
            'Nazwa' => 'Example legal person',
            'Typ' => 'P',
            'SilosID' => '6',
        ], $result);
    }

    public function testItPreservesTheHistoricalSingleFRecordShape(): void
    {
        $result = $this->parser()->parse($this->fixture('search-single-f.xml'));

        self::assertSame([
            'Regon' => '123456789',
            'Nip' => '9876543210',
            'Nazwa' => 'Example natural person',
            'Typ' => 'F',
            'SilosID' => '1',
        ], $result);
    }

    public function testItReturnsAnEmptyArrayWhenTheResponseContainsNoRecords(): void
    {
        self::assertSame([], $this->parser()->parse($this->fixture('search-empty.xml')));
    }

    public function testItPreservesEveryMixedRecordInTheOriginalOrder(): void
    {
        $result = $this->parser()->parse($this->fixture('search-mixed-krs-0000003157.xml'));

        self::assertCount(7, $result);
        self::assertSame(
            ['LP', 'LP', 'LP', 'LP', 'P', 'LP', 'LP'],
            array_column($result, 'Typ')
        );
        self::assertSame('276854946', $result[4]['Regon']);
    }

    public function testItNormalizesEmptyAndNestedFieldsInASingleRecord(): void
    {
        $result = $this->parser()->parse(
            '<root><dane><Regon>001234567</Regon><Nazwa>Żółta Spółka</Nazwa><Typ>P</Typ>'
                . '<Nip/><Adres><KodPocztowy>00-001</KodPocztowy><NrLokalu/></Adres></dane></root>'
        );

        self::assertSame([
            'Regon' => '001234567',
            'Nazwa' => 'Żółta Spółka',
            'Typ' => 'P',
            'Nip' => [],
            'Adres' => ['KodPocztowy' => '00-001', 'NrLokalu' => []],
        ], $result);
    }

    public function testItNormalizesEveryRecordWithoutCollapsingTheList(): void
    {
        $result = $this->parser()->parse(
            '<root><dane><Regon>001234567</Regon><Typ>F</Typ><SilosID>1</SilosID><Nip/></dane>'
                . '<dane><Regon>001234567</Regon><Typ>F</Typ><SilosID>2</SilosID><Nip/></dane></root>'
        );

        self::assertSame([
            ['Regon' => '001234567', 'Typ' => 'F', 'SilosID' => '1', 'Nip' => []],
            ['Regon' => '001234567', 'Typ' => 'F', 'SilosID' => '2', 'Nip' => []],
        ], $result);
    }

    public function testItTurnsMalformedXmlIntoAControlledServiceFailure(): void
    {
        $this->expectException(RegonServiceCallFailedException::class);

        $this->parser()->parse($this->fixture('search-malformed.xml'));
    }

    private function parser(): RegonSearchResponseParser
    {
        return new RegonSearchResponseParser();
    }

    private function fixture(string $name): string
    {
        $path = __DIR__ . '/../Fixtures/' . $name;
        $contents = file_get_contents($path);

        self::assertIsString($contents);

        return $contents;
    }
}
