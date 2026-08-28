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
