<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Game;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\AgainstGame;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validity;
use SportsPlanning\Output\Planning as PlanningOutput;
use SportsPlanning\Planning\State as PlanningState;
use SportsScheduler\Planning\Validator as PlanningValidator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class CreatorTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testGameInstanceAgainst(): void
    {
        $refereeInfo = new RefereeInfo();
        $input = $this->createInput([2], null, $refereeInfo);
        $planning = $this->createPlanning($input);
        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $singleSportVariantWithFields = $this->getSingleSportVariantWithFields(2);
        $refereeInfo = new RefereeInfo();
        $input = $this->createInput([2], [$singleSportVariantWithFields], $refereeInfo);
        $planning = $this->createPlanning($input);

//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//
//        $gameCreator = new GameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);

        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }

    public function testMixedGameModes(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(2, 2, 2, 3),
            $this->getSingleSportVariantWithFields(2, 4, 2),
        ];

        $planning = $this->createPlanning($this->createInput([4], $sportVariants));
//        (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(3, $planning->getAgainstGames());
        self::assertCount(8, $planning->getTogetherGames());
    }

    public function testAgainstBasic(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->createLogger());
        $gameAssigner->assignGames($planning);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(PlanningState::Succeeded, $planning->getState());
    }

    public function testAgainst(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(2, 1, 1, 2),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(2, 2), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->createLogger());
        $gameAssigner->assignGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(PlanningState::Succeeded, $planning->getState());
    }

    // [3]-against : 1vs1 : h2h-nrofgamesperplace => 2-0 f(1)-strat=>eql-ref(0:), batchGames 1->1, gamesInARow 2
    public function testAgainstH2H2(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
        ];
        $refereeInfo = new RefereeInfo();
        $input = $this->createInput([3], $sportVariants, $refereeInfo);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning, true);
        self::assertSame(Validity::VALID, $validity);
    }


    public function testAgainstMixed(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 3),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 4);

//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->createLogger());
        $gameAssigner->assignGames($planning);
//
//         (new PlanningOutput())->outputWithGames($planning, true);
//
        self::assertEquals(PlanningState::Succeeded, $planning->getState());

        self::assertEquals(3, $planning->createFirstBatch()->getLeaf()->getNumber());
    }

    public function test1Poule12Places(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(6),
        ];
        $input = $this->createInput([14], $sportVariants);
        $planning = new Planning($input, new SportRange(6, 6), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        $validator = new PlanningValidator();

        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function testSportSingle2Poules6Places(): void
    {
        // [6,6] - [single(4) gpp=>2 f(2)] - gpstrat=>eql - ref=>0:
        $sportVariants = [
            $this->getSingleSportVariantWithFields(2, 2, 4),
        ];
        $input = $this->createInput([6, 6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 2), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        $validator = new PlanningValidator();

        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }
}
