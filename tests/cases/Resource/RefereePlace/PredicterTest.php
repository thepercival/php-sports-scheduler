<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Resource\RefereePlace\Predicter;
use SportsScheduler\TestHelper\PlanningCreator;
// use SportsScheduler\TestHelper\PlanningReplacer;

class PredicterTest extends TestCase
{
    use PlanningCreator;
//    use PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $planning = $this->createPlanning(
            $this->createInput([3], null, $refereeInfo)
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
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectException(\Exception::class);
        $this->createPlanning(
            $this->createInput([2], null, $refereeInfo)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $planning = $this->createPlanning(
            $this->createInput([3, 3], null, $refereeInfo)
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
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules, 2));
        $planning = $this->createPlanning(
            $this->createInput([5, 4], null, $refereeInfo)
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
