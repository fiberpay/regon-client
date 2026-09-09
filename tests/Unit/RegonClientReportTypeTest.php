<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fiberpay\RegonClient\RegonClient;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class RegonClientReportTypeTest extends TestCase
{
    public function testItUsesTheGusNameForDeletedNaturalPersonActivityReport(): void
    {
        self::assertSame(
            'BIR11OsFizycznaDzialalnoscSkreslonaDo20141108',
            RegonClient::REPORT_TYPE_NATURAL_PERSON_DELETED_ACTIVITY
        );

        $validator = new ReflectionMethod(RegonClient::class, 'validateReportType');
        $validator->setAccessible(true);

        $validator->invoke(new RegonClient(), RegonClient::REPORT_TYPE_NATURAL_PERSON_DELETED_ACTIVITY);
    }

    public function testItDoesNotAcceptThePreviousDeletedNaturalPersonActivityReportName(): void
    {
        $validator = new ReflectionMethod(RegonClient::class, 'validateReportType');
        $validator->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);

        $validator->invoke(new RegonClient(), 'BIR11OsFizycznaDzialalnoscSkreslona');
    }

    #[DataProvider('naturalPersonActivityReportTypes')]
    public function testItAcceptsEachNaturalPersonActivityReportType(string $reportType): void
    {
        $validator = new ReflectionMethod(RegonClient::class, 'validateReportType');
        $validator->setAccessible(true);

        self::assertNull($validator->invoke(new RegonClient(), $reportType));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function naturalPersonActivityReportTypes(): iterable
    {
        return [
            'CEIDG' => [RegonClient::REPORT_TYPE_NATURAL_PERSON_CEIDG],
            'agricultural activity' => [RegonClient::REPORT_TYPE_NATURAL_PERSON_AGRICULTURAL_ACTIVITY],
            'other activity' => [RegonClient::REPORT_TYPE_NATURAL_PERSON_OTHER_ACTIVITY],
            'deleted activity' => [RegonClient::REPORT_TYPE_NATURAL_PERSON_DELETED_ACTIVITY],
        ];
    }
}
