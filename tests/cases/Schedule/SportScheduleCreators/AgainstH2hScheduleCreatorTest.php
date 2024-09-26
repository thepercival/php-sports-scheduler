<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\SportScheduleCreators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\PlanningOutput\Extra;
use SportsPlanning\Output\ScheduleOutput;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validity;
use SportsScheduler\Game\Creator as GameCreator;
use SportsScheduler\Planning\Validator as PlanningValidator;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class AgainstH2HScheduleCreatorTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function test2PlacesNrOfH2hIs1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([2], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

//        (new ScheduleOutput($this->createLogger()))->output( $schedules );
//        (new ScheduleOutput($this->createLogger()))->outputTotals( $schedules );

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->output( $planning, Extra::Games->value + Extra::Totals->value );

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator($this->createLogger());
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test2PlacesNrOfH2hIs2(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([2], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

//        (new ScheduleOutput($this->createLogger()))->output( $schedules );
//        (new ScheduleOutput($this->createLogger()))->outputTotals( $schedules );

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->output( $planning, Extra::Games->value + Extra::Totals->value );

        self::assertCount(2, $planning->getAgainstGames());
        $validator = new PlanningValidator($this->createLogger());
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test3PlacesNrOfH2hIs1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 1),
        ];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        (new ScheduleOutput($this->createLogger()))->output( $schedules );
        (new ScheduleOutput($this->createLogger()))->outputTotals( $schedules );

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->output( $planning, Extra::Games->value + Extra::Totals->value );

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator($this->createLogger());
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(6, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places5H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places6H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places15H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([15], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(105, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places16H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([16], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(120, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places17H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([17], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(136, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places18H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([18], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(153, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places19H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([19], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(171, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places20H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([20], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(190, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2H2(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(Validity::VALID, $validator->validate($planning, true));
    }
}
