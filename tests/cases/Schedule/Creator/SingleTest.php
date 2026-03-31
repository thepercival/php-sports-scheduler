<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\Creator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class SingleTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSimple(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 2, 2)
        ];
        $input = $this->createInput([7], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(7, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(7, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces1GamePerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 2, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(5, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlaceRandom(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 2, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(5, $planning->getTogetherGames());
    }

    public function testTwoSingleSports(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 1, 2),
            $this->getSingleSportVariantWithFields(2, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

//        $getPlacesDescription = function (array $togetherGamePlaces): string {
//            $description = "";
//            foreach ($togetherGamePlaces as $togetherGamePlace) {
//                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
//            }
//            return $description;
//        };

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        self::assertCount(6, $planning->getTogetherGames());
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }

    public function test4Places1GamePlaces1GamesPerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(1, 1, 1),
            $this->getSingleSportVariantWithFields(1, 1, 1)
        ];
        $input = $this->createInput([4], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(4, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(8, $planning->getTogetherGames());
    }



    public function test3Places2GamePlaces1GamesPerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $input = $this->createInput([3], $sportVariantsWithFields);
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
        // (new PlanningOutput())->outputWithGames($planning, true);

        $place3 = $input->getPoule(1)->getPlace(3);

        $togetherGames = $planning->getTogetherGames();
        self::assertCount(2, $togetherGames);

        $secondGame = $togetherGames->last();
        self::assertInstanceOf(TogetherGame::class, $secondGame);
        $secondGameGamePlaces = $secondGame->getPlaces();
        $secondGameOnlyGamePlace = $secondGameGamePlaces->first();
        self::assertInstanceOf(TogetherGamePlace::class, $secondGameOnlyGamePlace);
        self::assertSame($secondGameOnlyGamePlace->getPlace(), $place3);

        self::assertSame(1, $secondGameOnlyGamePlace->getGameRoundNumber());
    }
}
