<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedules\CycleCreators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Output\ScheduleCycleTogetherOutput;
use SportsPlanning\Planning;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsScheduler\Schedules\CycleCreator as ScheduleCreator;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorTogether;
use SportsScheduler\TestHelper\LoggerCreator;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\ScheduleHelper;

class CycleCreatorTogetherTest extends TestCase
{
    use LoggerCreator;
    use PlanningCreator;
    use ScheduleHelper;

    public function testOneCycleSimple(): void
    {
        $sportWithNrOfNrOfCycles = new SportWithNrOfCycles(
            new TogetherSport(2), 1
        );
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(
            7, [$sportWithNrOfNrOfCycles]
        );
        $this->isScheduleValid($scheduleWithNrOfPlaces, 4);
    }

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
        $cycleCreatorTogether = new CycleCreatorTogether($this->createLogger());

        $togetherNrCounterMap = new TogetherNrCounterMap($scheduleWithNrOfPlaces->nrOfPlaces);
        foreach( $scheduleWithNrOfPlaces->getTogetherSportSchedules() as $togetherSchedule ) {
            $rootCycle = $cycleCreatorTogether->createRootCycleAndGames($togetherSchedule, $togetherNrCounterMap );
            (new ScheduleCycleTogetherOutput())->output($rootCycle);
            self::assertCount($expectedNrOfGames, $this->getTogetherGames($rootCycle));
        }
    }
}
