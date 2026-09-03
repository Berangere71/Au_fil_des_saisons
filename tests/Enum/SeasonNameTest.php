<?php

namespace App\Tests\Enum;

use App\Enum\SeasonName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeasonNameTest extends TestCase
{
    /**
     * @return iterable<string, array{string, SeasonName}>
     */
    public static function boundaryDates(): iterable
    {
        yield 'dernier jour hiver' => ['2026-03-19', SeasonName::HIVER];
        yield 'premier jour printemps' => ['2026-03-20', SeasonName::PRINTEMPS];
        yield 'dernier jour printemps' => ['2026-06-20', SeasonName::PRINTEMPS];
        yield 'premier jour été' => ['2026-06-21', SeasonName::ETE];
        yield 'dernier jour été' => ['2026-09-22', SeasonName::ETE];
        yield 'premier jour automne' => ['2026-09-23', SeasonName::AUTOMNE];
        yield 'dernier jour automne' => ['2026-12-21', SeasonName::AUTOMNE];
        yield 'premier jour hiver' => ['2026-12-22', SeasonName::HIVER];
    }

    #[DataProvider('boundaryDates')]
    public function testSeasonIsResolvedOnExactBoundary(string $date, SeasonName $expected): void
    {
        self::assertSame($expected, SeasonName::fromDate(new \DateTimeImmutable($date)));
    }
}
