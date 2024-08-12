<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\Creator;

use SportsPlanning\Output\Schedule as ScheduleOutput;
use SportsScheduler\Game\Creator as GameCreator;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Planning;
use SportsPlanning\Output\Planning as PlanningOutput;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class AllInOneGameTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSimple(): void
    {
        $sportVariant = $this->getAllInOneGameSportVariantWithFields(2, 3);
        $input = $this->createInput([3, 3, 3], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(9, $planning->getTogetherGames());
    }
}
