<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning\PlanningValidity;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Planning\PlanningValidator as PlanningValidator;
use SportsScheduler\TestHelper\PlanningCreator;

final class PerformanceTest extends TestCase
{
    use PlanningCreator;

    // [5,4,4,4,4,4] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:SP
    public function testUnbalancedHighMinNrOfBatchGames(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(4, 4);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5, 4, 4, 4, 4, 4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration, $nrOfGamesPerBatchRange);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planningWithMeta);
        self::assertSame(PlanningValidity::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // echo "============ " . (microtime(true) - $time_start);

        self::assertLessThan(1.0, microtime(true) - $time_start);
    }

    public function testSelfRefereeRange7to7(): void
    {
        // $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(7, 7);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 9, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([7, 7, 7, 7]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration, $nrOfGamesPerBatchRange);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planningWithMeta);
        self::assertSame(PlanningValidity::VALID, $validity);
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

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 9, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([7, 7, 7, 7]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration, $nrOfGamesPerBatchRange,4);

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planningWithMeta);
        self::assertSame(PlanningValidity::VALID, $validity);
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

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 4, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration, $nrOfGamesPerBatchRange);
//        self::assertEquals(
//            '[2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(4)] - ref=>0:',
//            $input->createConfiguration()->getName()
//        );

//        (new PlanningOutput())->outputWithGames($planning, true);

//        $planningValidator = new PlanningValidator($this->createLogger());
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//         echo "============ " . (microtime(true) - $time_start);
//
//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertEquals(4, $planningWithMeta->createFirstBatch()->getLeaf()->getNumber());
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
//        $validator = new PlanningValidator($this->createLogger());
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }

}
