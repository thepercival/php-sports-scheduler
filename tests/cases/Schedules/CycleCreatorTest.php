<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedules;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Output\ScheduleCyclePartAgainstOutput;
use SportsPlanning\Output\ScheduleCycleTogetherOutput;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsScheduler\Schedules\CycleCreator;
use SportsScheduler\TestHelper\PlanningCreator;

class CycleCreatorTest extends TestCase
{
    use PlanningCreator;


    public function testTwoCycles(): void
    {
        $sportWithNrOfNrOfCycles = new SportWithNrOfCycles(
            new TogetherSport(2), 2
        );
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(
            7, [$sportWithNrOfNrOfCycles]
        );
        $this->isScheduleValid($scheduleWithNrOfPlaces, 7);
    }

    public function isScheduleValid(ScheduleWithNrOfPlaces $scheduleWithNrOfPlaces, int $expectedNrOfGames): void
    {
        $cycleCreator = new CycleCreator($this->createLogger());

        $sportRootCycles = $cycleCreator->createSportRootCycles($scheduleWithNrOfPlaces);
        foreach( $sportRootCycles as $rootCycle ) {
            if( $rootCycle instanceof ScheduleCycleTogether ) {
                (new ScheduleCycleTogetherOutput())->output($rootCycle);
                self::assertCount($expectedNrOfGames, $rootCycle->getAllGames());
            } else {
                (new ScheduleCyclePartAgainstOutput())->output($rootCycle->firstPart, true);
                self::assertCount($expectedNrOfGames, $rootCycle->getAllCyclePartGames());
            }
        }
    }
}
