<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedules\CycleCreators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorAgainstOneVsOne;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\ScheduleHelper;

class CycleCreatorAgainstOneVsOneTest extends TestCase
{
    use PlanningCreator;
    use ScheduleHelper;

    public function testBasics(): void
    {
        $this->hasValidNrOfGames(2, 1, 1 * 1);
        $this->hasValidNrOfGames(2, 2, 1 * 2);
        $this->hasValidNrOfGames(3, 1, 3 * 1);
        $this->hasValidNrOfGames(3, 2, 3 * 2);
        $this->hasValidNrOfGames(4, 1, 6 * 1);
        $this->hasValidNrOfGames(4, 2, 6 * 2);
    }

    public function hasValidNrOfGames(int $nrOfPlaces, int $nrOfCycles, int $expectedNrOfGames): void
    {
        $cycleCreator = new CycleCreatorAgainstOneVsOne($this->createLogger());

        $sportWithNrOfNrOfCycles = new SportWithNrOfCycles(new AgainstOneVsOne(), $nrOfCycles);
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces($nrOfPlaces, [$sportWithNrOfNrOfCycles]);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $rootCycle = $cycleCreator->createRootCycleAndGames($sportSchedule);

//        (new ScheduleCyclePartAgainstOutput())->output($rootCycle->firstPart, false);

        self::assertCount($expectedNrOfGames, $this->getAgainstOneVsOneGames($rootCycle));
    }
}
