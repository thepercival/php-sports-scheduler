<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsScheduler\TestHelper\PlanningCreator;

final class PlaceNrCombinationTest extends TestCase
{
    use PlanningCreator;

    public function testCount(): void
    {
        $placeNrCombination = new PlaceNrCombination([1,2,3,4]);
        self::assertSame(4, $placeNrCombination->count());
    }

    public function testToString(): void
    {
        $placeNrs = range(1, 4);
        $placeNrCombination = new PlaceNrCombination($placeNrs);
        self::assertSame(   '1 & 2 & 3 & 4'/*15*/, (string)$placeNrCombination);
    }
}
