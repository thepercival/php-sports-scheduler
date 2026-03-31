<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\Validators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\PlanningOutput\Extra;
use SportsPlanning\Output\ScheduleOutput;
use SportsScheduler\Combinations\Validators\AgainstValidator as AgainstValidator;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsPlanning\Planning;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class AgainstValidatorTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSimple(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $input = $this->createInput([2], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $maxGppMargin = $this->getMaxGppMargin(2, $sportVariants, $this->getLogger() );
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test4Places1VS1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $input = $this->createInput([4], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());

        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $maxGppMargin = $this->getMaxGppMargin(4, $sportVariants, $this->getLogger() );
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        $extras = Extra::NrOfBatchGamesRange->value;
        // (new PlanningOutput($this->getLogger()))->output($planning, $extras);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places1VS1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test6Places1VS1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $input = $this->createInput([6], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $maxGppMargin = $this->getMaxGppMargin(6, $sportVariants, $this->getLogger() );
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places2VS2(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 12)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

//    public function test6Places2VS2(): void
//    {
//        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 8);
//        $input = $this->createInput([6], [$sportVariant]);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $counter = new AgainstAndAgainstCounter($input->getPoule(1), $input->getSport(1));
//        $counter->addGames($planning);
//        echo $counter;
//
//        self::assertTrue($counter->balanced());
//    }
}
