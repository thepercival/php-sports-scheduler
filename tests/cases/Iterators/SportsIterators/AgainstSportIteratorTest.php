<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Iterators\SportsIterators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsScheduler\Iterators\SportsIterators\AgainstSportIterator;
use SportsScheduler\TestHelper\PlanningCreator;

class AgainstSportIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeNrOfCycles = new SportRange(1, 2);
        $sportsIterator = new AgainstSportIterator($rangeNrOfFields, $rangeNrOfCycles);

        $sportWithNrOfFieldsAndNrOfCycles = $sportsIterator->current();
        self::assertNotNull($sportWithNrOfFieldsAndNrOfCycles);
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        self::assertInstanceOf(AgainstOneVsOne::class, $sport);
        self::assertEquals(2, $sport->getNrOfGamePlaces());
        self::assertEquals(1, $sportWithNrOfFieldsAndNrOfCycles->nrOfFields);
//        self::assertEquals(1, $sportVariant->getNrOfH2H());
    }

    public function testLast(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeNrOfCycles = new SportRange(1, 2);
        $sportsIterator = new AgainstSportIterator($rangeNrOfFields, $rangeNrOfCycles);

        $sportWithNrOfFieldsAndNrOfCycles = null;
        while ($sportsIterator->current() !== null) {
            $sportWithNrOfFieldsAndNrOfCycles = $sportsIterator->current();
            $sportsIterator->next();
        }
        self::assertNotNull($sportWithNrOfFieldsAndNrOfCycles);
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        self::assertInstanceOf(AgainstTwoVsTwo::class, $sport);

        self::assertEquals(4, $sport->getNrOfGamePlaces());
        self::assertEquals(2, $sportWithNrOfFieldsAndNrOfCycles->nrOfFields);
    }

    public function testNrOfPossibilitiesSmallRange(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeNrOfCycles = new SportRange(1, 2);
        $sportIterator = new AgainstSportIterator($rangeNrOfFields, $rangeNrOfCycles);

        $nrOfPossibilities = 0;
        while ($sportIterator->valid()) {
            // echo json_encode($sportIterator->current()) . PHP_EOL;
            $nrOfPossibilities++;
            $sportIterator->next();
        }
        $sportIterator->next(); // should do nothing
        self::assertFalse($sportIterator->valid());
        self::assertEquals(12, $nrOfPossibilities);
    }

    public function testNrOfPossibilitiesLargerRange(): void
    {
        $rangeNrOfFields = new SportRange(1, 5);
        $rangeNrOfCycles = new SportRange(1, 4);
        $sportsIterator = new AgainstSportIterator($rangeNrOfFields, $rangeNrOfCycles);

        $nrOfPossibilities = 0;
        while ($sportsIterator->valid()) {
//            echo json_encode($sportsIterator->current()) . PHP_EOL;
            $nrOfPossibilities++;
            $sportsIterator->next();
        }
        $sportsIterator->next(); // should do nothing
        self::assertFalse($sportsIterator->valid());
        self::assertEquals(60, $nrOfPossibilities);
    }
}
