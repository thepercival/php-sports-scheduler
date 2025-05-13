<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Game;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Input;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Game\GameAssigner as GameAssigner;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validity;
use SportsPlanning\Output\PlanningOutput;
use SportsScheduler\Game\PlannableGameCreator;
use SportsScheduler\Planning\PlanningValidator as PlanningValidator;
use SportsScheduler\TestHelper\PlanningCreator;

class CreatorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $config = new Input\Configuration(
            new PouleStructure(2),
            [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 2)],
            new PlanningRefereeInfo(),
            false
        );
        $nrOfBatchGamesRange = new SportRange(1,1);
        $planning = new Planning(new Input($config), $nrOfBatchGamesRange, 0);

        $sportCyclesMap = $this->createSportCyclesMap($config);
        $gameCreator = new PlannableGameCreator($this->createLogger());
        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);

        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $config = new Input\Configuration(
            new PouleStructure(2),
            [
                new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 2)
            ],
            new PlanningRefereeInfo(),
            false
        );
        $nrOfBatchGamesRange = new SportRange(1,1);
        $planning = new Planning(new Input($config), $nrOfBatchGamesRange, 0);

        $sportCyclesMap = $this->createSportCyclesMap($config);
        $gameCreator = new PlannableGameCreator($this->createLogger());
        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);

        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }

    public function testMixedGameModes(): void
    {
        $config = new Input\Configuration(
            new PouleStructure(4),
            [
                new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 2, 1),
                new SportWithNrOfFieldsAndNrOfCycles(new TogetherSport(2), 2, 4)
            ],
            new PlanningRefereeInfo(),
            false
        );
        $nrOfBatchGamesRange = new SportRange(1,1);
        $planning = new Planning(new Input($config), $nrOfBatchGamesRange, 0);

        $sportCyclesMap = $this->createSportCyclesMap($config);
        $gameCreator = new PlannableGameCreator($this->createLogger());
        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);

//        (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(3, $planning->getAgainstGames());
        self::assertCount(8, $planning->getTogetherGames());
    }

//    public function testAgainstBasic(): void
//    {
//        $config = new Input\Configuration(
//            new PouleStructure(5),
//            [
//                new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
//            ],
//            new PlanningRefereeInfo(),
//            false
//        );
//        $planning = $this->createPlanning($config);
//
//        $sportCyclesMap = $this->createSportCyclesMap($config);
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);
//
//
////        (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertEquals(PlanningState::Succeeded, $planning->getState());
//    }

//    public function testAgainst(): void
//    {
//        $config = new Input\Configuration(
//            new PouleStructure(5),
//            [
//                new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
//            ],
//            new PlanningRefereeInfo(),
//            false
//        );
//        $nrOfBatchGamesRange = new SportRange(1,1);
//        $planning = new Planning(new Input($config), $nrOfBatchGamesRange, 0);
//
//        $sportCyclesMap = $this->createSportCyclesMap($config);
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);
//
////        $gameGenerator = new GameGenerator();
////        $gameGenerator->generateUnassignedGames($planning);
////        (new PlanningOutput())->outputWithGames($planning, true);
//
//
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertEquals(PlanningState::Succeeded, $planning->getState());
//    }

    // [3]-against : 1vs1 : h2h-nrofgamesperplace => 2-0 f(1)-strat=>eql-ref(0:), batchGames 1->1, gamesInARow 2
//    public function testAgainstH2H2(): void
//    {
//        $sportVariants = [
//            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
//        ];
//        $refereeInfo = new PlanningRefereeInfo();
//        $input = $this->createInput([3], $sportVariants, $refereeInfo);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $biggestPoule = $input->getPoule(1);
//        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
//        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
//
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);
//
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator($this->createLogger());
//        $validity = $planningValidator->validate($planning, true);
//        self::assertSame(Validity::VALID, $validity);
//    }
//
//
//    public function testAgainstMixed(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 3),
//        ];
//        $input = $this->createInput([5], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 4);
//
////        $gameGenerator = new GameGenerator($this->getLogger());
////        $gameGenerator->generateUnassignedGames($planning);
////        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $biggestPoule = $input->getPoule(1);
//        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
//        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
//
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);
//
//        $gameAssigner = new GameAssigner($this->createLogger());
//        $gameAssigner->assignGames($planning);
////
////         (new PlanningOutput())->outputWithGames($planning, true);
////
//        self::assertEquals(PlanningState::Succeeded, $planning->getState());
//
//        self::assertEquals(3, $planning->createFirstBatch()->getLeaf()->getNumber());
//    }
//
//    public function test1Poule12Places(): void
//    {
//        $sportVariants = [
//            $this->getAgainstH2hSportVariantWithFields(6),
//        ];
//        $input = $this->createInput([14], $sportVariants);
//        $planning = new Planning($input, new SportRange(6, 6), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $biggestPoule = $input->getPoule(1);
//        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
//        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
//
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);
//
////        (new PlanningOutput())->outputWithGames($planning, true);
////        (new PlanningOutput())->outputWithTotals($planning, false);
//
//        $validator = new PlanningValidator($this->createLogger());
//
//        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
//    }
//
//    public function testSportSingle2Poules6Places(): void
//    {
//        // [6,6] - [single(4) gpp=>2 f(2)] - gpstrat=>eql - ref=>0:
//        $sportVariants = [
//            $this->getSingleSportVariantWithFields(2, 2, 4),
//        ];
//        $input = $this->createInput([6, 6], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 2), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $biggestPoule = $input->getPoule(1);
//        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
//        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
//
//        $gameCreator = new PlannableGameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);
//
////        (new PlanningOutput())->outputWithGames($planning, true);
////        (new PlanningOutput())->outputWithTotals($planning, false);
//
//        $validator = new PlanningValidator($this->createLogger());
//
//        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
//    }



}
