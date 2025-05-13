<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Resource\RefereePlace\Predicter;
use SportsScheduler\TestHelper\PlanningCreator;
// use SportsScheduler\TestHelper\PlanningReplacer;

class PredicterTest extends TestCase
{
    use PlanningCreator;
//    use PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $refereeInfo = new PlanningRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new Configuration(new PouleStructure(3), $sportWithNrOfFieldsAndNrOfCycles, $refereeInfo, false)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::SamePoule);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces(): void
    {
        $refereeInfo = new PlanningRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectException(\Exception::class);
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $this->createPlanning(
            new Configuration(new PouleStructure(2), $sportWithNrOfFieldsAndNrOfCycles, $refereeInfo, false)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $refereeInfo = new PlanningRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new Configuration(new PouleStructure(3, 3), $sportWithNrOfFieldsAndNrOfCycles, $refereeInfo, false)
        );

        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OtherPoules);
        self::assertTrue($canStillAssign);
    }

    public function testOtherPoulesEnoughRefereePlacesWithMultipleSimRefs(): void
    {
        $refereeInfo = new PlanningRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules, 2));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new Configuration(new PouleStructure(5, 4), $sportWithNrOfFieldsAndNrOfCycles, $refereeInfo, false)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OtherPoules);
        self::assertTrue($canStillAssign);
    }
}
