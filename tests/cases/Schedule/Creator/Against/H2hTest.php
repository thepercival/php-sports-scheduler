<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\Creator\Against;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsPlanning\Planning;
use SportsScheduler\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Validity;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class H2hTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function test1V1Places2H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([2], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(2, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
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

    public function test1V1Places3H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
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

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
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

        self::assertCount(6, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places5H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
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

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places6H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([6], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(6, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places15H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([15], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(15, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(105, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places16H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([16], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(16, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(120, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places17H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([17], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(17,$sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(136, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places18H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([18], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(18, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(153, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places19H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([19], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(19, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(171, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places20H2h1(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([20], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(20,$sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(190, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2h2(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
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

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }
}
