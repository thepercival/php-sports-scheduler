<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Iterators\SportsIterators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsScheduler\Iterators\SportsIterators\TogetherSportIterator;
use SportsScheduler\TestHelper\PlanningCreator;

class TogetherSportIteratorTest extends TestCase
{
    use PlanningCreator;

//    public function testRewind(): void
//    {
//        $rangeNrOfFields = new SportRange(1, 2);
//        $rangeNrOfCycles = new SportRange(1, 2);
//        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeNrOfCycles);
//
//        $sportWithNrOfFieldsAndNrOfCycles = $sportsIterator->current();
//        self::assertNotNull($sportWithNrOfFieldsAndNrOfCycles);
//        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
//        self::assertInstanceOf(AgainstOneVsOne::class, $sport);
//        self::assertEquals(2, $sport->getNrOfGamePlaces());
//        self::assertEquals(1, $sportWithNrOfFieldsAndNrOfCycles->nrOfFields);
////        self::assertEquals(1, $sportVariant->getNrOfH2H());
//    }
//
//    public function testLast(): void
//    {
//        $rangeNrOfFields = new SportRange(1, 2);
//        $rangeNrOfCycles = new SportRange(1, 2);
//        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeNrOfCycles);
//
//        $sportWithNrOfFieldsAndNrOfCycles = null;
//        while ($sportsIterator->current() !== null) {
//            $sportWithNrOfFieldsAndNrOfCycles = $sportsIterator->current();
//            $sportsIterator->next();
//        }
//        self::assertNotNull($sportWithNrOfFieldsAndNrOfCycles);
//        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
//        self::assertInstanceOf(AgainstTwoVsTwo::class, $sport);
//
//        self::assertEquals(4, $sport->getNrOfGamePlaces());
//        self::assertEquals(2, $sportWithNrOfFieldsAndNrOfCycles->nrOfFields);
//    }

    public function testNrOfPossibilitiesSmallRange(): void
    {
        $rangeNrOfGamePlaces = new SportRange(1, 2);
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeNrOfCycles = new SportRange(1, 2);
        $sportsIterator = new TogetherSportIterator($rangeNrOfGamePlaces, $rangeNrOfFields, $rangeNrOfCycles);

        $nrOfPossibilities = 0;
        while ($sportsIterator->current()) {
//            echo json_encode($sportWithNrOfFieldsAndNrOfCycles) . PHP_EOL;
            $nrOfPossibilities++;
            $sportsIterator->next();
        }
        $sportsIterator->next(); // should do nothing
        self::assertFalse($sportsIterator->valid());
        self::assertEquals(8, $nrOfPossibilities);
    }

//    public function testNrOfPossibilitiesLargerRange(): void
//    {
//        $rangeNrOfFields = new SportRange(1, 5);
//        $rangeNrOfCycles = new SportRange(1, 4);
//        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeNrOfCycles);
//
//        $nrOfPossibilities = 0;
//        while ($sportsIterator->valid()) {
//            // echo json_encode($sportsIterator->current()) . PHP_EOL;
//            $nrOfPossibilities++;
//            $sportsIterator->next();
//        }
//        $sportsIterator->next(); // should do nothing
//        self::assertFalse($sportsIterator->valid());
//        self::assertEquals(60, $nrOfPossibilities);
//    }
}
