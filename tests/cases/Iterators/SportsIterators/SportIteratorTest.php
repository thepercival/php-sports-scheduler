<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Iterators\SportsIterators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsScheduler\Iterators\SportsIterators\SportIterator;
use SportsScheduler\TestHelper\PlanningCreator;

final class SportIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testNrOfPossibilitiesSmallRange(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeNrOfAgainstCycles = new SportRange(1, 2);
        $rangeNrOfTogetherCycles = new SportRange(1, 2);
        $rangeNrOfTogetherGamePlaces = new SportRange(1, 2);

        $sportIterator = new SportIterator(
            $rangeNrOfFields,
            $rangeNrOfAgainstCycles,
            $rangeNrOfTogetherCycles,
            $rangeNrOfTogetherGamePlaces);

        $nrOfPossibilities = 0;
        while ($sportIterator->valid()) {
            // echo json_encode($sportIterator->current()) . PHP_EOL;
            $nrOfPossibilities++;
            $sportIterator->next();
        }
        $sportIterator->next(); // should do nothing
        self::assertFalse($sportIterator->valid());
        self::assertEquals(20, $nrOfPossibilities);
    }
}
