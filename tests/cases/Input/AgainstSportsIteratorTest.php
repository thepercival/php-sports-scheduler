<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Input;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsScheduler\Input\AgainstSportsIterator;
use SportsScheduler\TestHelper\PlanningCreator;

class AgainstSportsIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

        $sportWithNrOfFieldsAndNrOfCycles = $sportsIterator->current();
        self::assertNotNull($sportWithNrOfFieldsAndNrOfCycles);
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        self::assertInstanceOf(AgainstOneVsOne::class, $sport);
        self::assertGreaterThan(50, $sportsIterator->key());
        self::assertEquals(2, $sport->getNrOfGamePlaces());
        self::assertEquals(1, $sportWithNrOfFieldsAndNrOfCycles->nrOfFields);
//        self::assertEquals(1, $sportVariant->getNrOfH2H());
    }

    public function testLast(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

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

    public function testCount(): void
    {
        $rangeNrOfFields = new SportRange(1, 2);
        $rangeGameAmount = new SportRange(1, 2);
        $sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);

        $nrOfPossibilities = 0;
        while ($sportsIterator->valid()) {
            // echo $sportsIterator->key() . PHP_EOL;
            $nrOfPossibilities++;
            $sportsIterator->next();
        }
        $sportsIterator->next(); // should do nothing
        self::assertFalse($sportsIterator->valid());
        self::assertEquals(12, $nrOfPossibilities);
    }
}
