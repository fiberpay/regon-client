<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fiberpay\RegonClient\RegonClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SimpleXMLElement;

final class RegonClientArrayConversionTest extends TestCase
{
    #[DataProvider('reportCases')]
    public function testReportsContainOnlyArraysAndScalarValues(string $xml, array $expected): void
    {
        // Exercise report conversion without connecting to the external SOAP service.
        $conversion = new ReflectionMethod(RegonClient::class, 'toArray');
        $result = $conversion->invoke(new RegonClient(), new SimpleXMLElement($xml));

        self::assertSame($expected, $result);
    }

    public static function reportCases(): array
    {
        return [
            'basic report with empty fields and leading zeros' => [
                '<dane><praw_nazwa>Żółta Spółka</praw_nazwa>'
                    . '<praw_numerWRejestrzeEwidencji>0000123456</praw_numerWRejestrzeEwidencji>'
                    . '<praw_adresEmail/><praw_numerTelefonu></praw_numerTelefonu></dane>',
                [
                    'praw_nazwa' => 'Żółta Spółka',
                    'praw_numerWRejestrzeEwidencji' => '0000123456',
                    'praw_adresEmail' => [],
                    'praw_numerTelefonu' => [],
                ],
            ],
            'single PKD entry keeps associative shape' => [
                '<root><dane><praw_pkdKod>0111Z</praw_pkdKod>'
                    . '<praw_pkdPrzewazajace>1</praw_pkdPrzewazajace><praw_pkdNazwa/></dane></root>',
                ['dane' => [
                    'praw_pkdKod' => '0111Z',
                    'praw_pkdPrzewazajace' => '1',
                    'praw_pkdNazwa' => [],
                ]],
            ],
            'multiple PKD entries retain order and empty nested fields' => [
                '<root><dane><praw_pkdKod>0111Z</praw_pkdKod><praw_pkdNazwa/></dane>'
                    . '<dane><praw_pkdKod>6201Z</praw_pkdKod>'
                    . '<praw_pkdNazwa>Programowanie</praw_pkdNazwa></dane></root>',
                ['dane' => [
                    ['praw_pkdKod' => '0111Z', 'praw_pkdNazwa' => []],
                    ['praw_pkdKod' => '6201Z', 'praw_pkdNazwa' => 'Programowanie'],
                ]],
            ],
            'empty report remains empty' => ['<root/>', []],
        ];
    }
}
