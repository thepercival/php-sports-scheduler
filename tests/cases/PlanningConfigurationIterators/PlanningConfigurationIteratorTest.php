<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\PlanningConfigurationIterators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsScheduler\PlanningConfigurationIterators\PlanningConfigurationIterator;
use SportsScheduler\TestHelper\PlanningCreator;

class PlanningConfigurationIteratorTest extends TestCase
{
    use PlanningCreator;

    public function testRewind(): void
    {
        $rangeNrOfTogetherCycles = new SportRange(1, 2);
        $configIterator = new PlanningConfigurationIterator(
            new SportRange(2, 6),
            new SportRange(2, 6),
            new SportRange(1, 3),
            new SportRange(0, 3),
            new SportRange(1, 3),
            new SportRange(1, 3),
            $rangeNrOfTogetherCycles,
            new SportRange(1, 4)
        );

        $planningConfig = $configIterator->current();
        self::assertNotNull($planningConfig);
        // self::assertGreaterThan(30, $inputIterator->key());
        self::assertEquals([2], $planningConfig->pouleStructure->toArray());
        self::assertSame(0, $planningConfig->refereeInfo->nrOfReferees);
        self::assertEquals(SelfReferee::Disabled, $planningConfig->refereeInfo->selfRefereeInfo->selfReferee);
    }

//    public function testLast()
//    {
//        $rangeNrOfFields = new Range(1, 2);
//        $rangeGameAmount = new Range(1, 2);
//        $sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);
//
//        $sportConfig = null;
//        while ($sportsIterator->current() !== null) {
//            $sportConfig = $sportsIterator->current();
//            $sportsIterator->next();
//        }
//        self::assertNotNull($sportConfig);
//
//        self::assertEquals(GameMode::TOGETHER, $sportConfig->getSport()->getGameMode());
//        self::assertEquals(2, $sportConfig->getSport()->getNrOfGamePlaces());
//        self::assertEquals(2, $sportConfig->getNrOfFields());
//        self::assertEquals(2, $sportConfig->getGameAmount());
//    }

    public function testCount(): void
    {
        $configIterator = new PlanningConfigurationIterator(
            new SportRange(2, 6),
            new SportRange(2, 6),
            new SportRange(1, 3),
            new SportRange(0, 3),
            new SportRange(1, 2),
            new SportRange(1, 2),
            new SportRange(1, 5),
            new SportRange(1, 4)
        );

        $nrOfPossibilities = 0;
        while ($configIterator->valid()) {
            // echo $inputIterator->key() . PHP_EOL;
            $nrOfPossibilities++;
            $configIterator->next();
        }
        $configIterator->next(); // should do nothing
        self::assertFalse($configIterator->valid());
        self::assertEquals(2050, $nrOfPossibilities);
        // last change => remove gamePlaceStrategy
    }
}
