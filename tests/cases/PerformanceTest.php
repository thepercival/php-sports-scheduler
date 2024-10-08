<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Planning;
use SportsPlanning\Output\Planning as PlanningOutput;
use SportsScheduler\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Validity;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsScheduler\TestHelper\PlanningCreator;

class PerformanceTest extends TestCase
{
    use PlanningCreator;

    // [5,4,4,4,4,4] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:SP
    public function testUnbalancedHighMinNrOfBatchGames(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(4, 4);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $planning = $this->createPlanning(
            $this->createInput(
                [5, 4, 4, 4, 4, 4],
                [$sportVariantsWithFields],
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule))
            ),
            $nrOfGamesPerBatchRange
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // echo "============ " . (microtime(true) - $time_start);

        self::assertLessThan(1.0, microtime(true) - $time_start);
    }

    public function testSelfRefereeRange7to7(): void
    {
        // $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(7, 7);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(9);
        $planning = $this->createPlanning(
            $this->createInput(
                [7, 7, 7, 7],
                [$sportVariantsWithFields],
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule))
            ),
            $nrOfGamesPerBatchRange/*,
            0, false, true*/
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//        echo "============ " . (microtime(true) - $time_start);

//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
//        self::assertLessThan(1.5, microtime(true) - $time_start);
    }

    // [7,7,7,7] - [against(1vs1) h2h:gpp=>1:0 f(9)] - gpstrat=>eql - ref=>0:SP
    public function testSelfReferee(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(8, 8);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(9);
        $planning = $this->createPlanning(
            $this->createInput(
                [7, 7, 7, 7],
                [$sportVariantsWithFields],
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule))
            ),
            $nrOfGamesPerBatchRange,
            4/*, true, true*/
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//         echo "============ " . (microtime(true) - $time_start);
//
//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertLessThan(2, microtime(true) - $time_start);
    }

    // [2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(4)] - gpstrat=>eql - ref=>0:
    public function testOnMinNrOfBatches(): void
    {
        // $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(4, 4);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(4);
        $input = $this->createInput(
            [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
            // 16 poules, 8 wedstrijden => 4 velden dus 4 wedstrijden dus 4 batches
            [$sportVariantsWithFields],
            new RefereeInfo()
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange/*, 0, true*/);
        self::assertEquals(
            '[2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(4)] - ref=>0:',
            $input->createConfiguration()->getName()
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//         echo "============ " . (microtime(true) - $time_start);
//
//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertEquals(4, $planning->createFirstBatch()->getLeaf()->getNumber());
    }

//    public function test2V2With18PlacesAnd26GamesPerPlace(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(4, 2, 2, 26),
//        ];
//        $input = $this->createInput([18], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $scheduleCreator->setAgainstGppMargin(1);
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->createLogger());
//        $gameCreator->createGames($planning, $schedules);
////        (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(117, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }

}
