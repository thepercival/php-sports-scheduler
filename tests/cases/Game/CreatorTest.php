<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Game;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Against as AgainstGame;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validity;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Planning\State as PlanningState;
use SportsScheduler\Planning\PlanningValidator as PlanningValidator;
use SportsPlanning\PlanningRefereeInfo;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class CreatorTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testGameInstanceAgainst(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(2)
        ];
        $input = $this->createInput([2], $sportVariantsWithFields, new PlanningRefereeInfo());
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = $this->createPlanning($input);
        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(2, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2)
        ];
        $input = $this->createInput([2], $sportVariantsWithFields, new PlanningRefereeInfo());

        $planning = $this->createPlanning($input);

//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//
//        $gameCreator = new GameCreator($this->getLogger());
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
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(PlanningState::Succeeded, $planning->getState());
    }

    public function testAgainst(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(2, 1, 1, 2),
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(2, 2), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(PlanningState::Succeeded, $planning->getState());
    }

    // [3]-against : 1vs1 : h2h-nrofgamesperplace => 2-0 f(1)-strat=>eql-ref(0:), batchGames 1->1, gamesInARow 2
    public function testAgainstH2h2(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([3], $sportVariantsWithFields, new PlanningRefereeInfo());
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(3, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning, true);
        self::assertSame(Validity::VALID, $validity);
    }


    public function testAgainstMixed(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 3),
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 4);

//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);
//
//         (new PlanningOutput())->outputWithGames($planning, true);
//
        self::assertEquals(PlanningState::Succeeded, $planning->getState());

        self::assertEquals(3, $planning->createFirstBatch()->getLeaf()->getNumber());
    }

    public function test1Poule12Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(6),
        ];
        $input = $this->createInput([14], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(6, 6), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(14, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        $validator = new PlanningValidator();

        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function testSportSingle2Poules6Places(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(2, 2, 4),
        ];
        // [6,6] - [single(4) gpp=>2 f(2)] - gpstrat=>eql - ref=>0:
        $input = $this->createInput([6,6], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 2), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(6, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        $validator = new PlanningValidator();

        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LogLevel::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}
