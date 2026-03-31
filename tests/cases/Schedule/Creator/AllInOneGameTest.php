<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\Creator;

use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Planning;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class AllInOneGameTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSimple(): void
    {
        $sportVariantsWithFields = [
            $this->getAllInOneGameSportVariantWithFields(2, 3)
        ];
        $input = $this->createInput([3, 3, 3], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(3, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(9, $planning->getTogetherGames());
    }
}
