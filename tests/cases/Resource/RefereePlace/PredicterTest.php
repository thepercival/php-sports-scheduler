<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Resource\RefereePlace\Predicter;
use SportsScheduler\TestHelper\PlanningCreator;

final class PredicterTest extends TestCase
{
    use PlanningCreator;
//    use PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new PlanningConfiguration(
                new PouleStructure([3]),
                $sportWithNrOfFieldsAndNrOfCycles,
                $refereeInfo,
                false
            )
        );
        $predicter = new Predicter($planning->poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoules
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::SamePoule);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces(): void
    {
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectException(\Exception::class);
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $this->createPlanning(
            new PlanningConfiguration(
                new PouleStructure([2]),
                $sportWithNrOfFieldsAndNrOfCycles,
                $refereeInfo,
                false
            )
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new PlanningConfiguration(
                new PouleStructure([3, 3]),
                $sportWithNrOfFieldsAndNrOfCycles,
                $refereeInfo,
                false
            )
        );

        $predicter = new Predicter($planning->poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoules
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OtherPoules);
        self::assertTrue($canStillAssign);
    }

    public function testOtherPoulesEnoughRefereePlacesWithMultipleSimRefs(): void
    {
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules, 2));
        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $planning = $this->createPlanning(
            new PlanningConfiguration(
                new PouleStructure([5, 4]),
                $sportWithNrOfFieldsAndNrOfCycles,
                $refereeInfo,
                false
            )
        );
        $poules = $planning->poules;
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoules
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OtherPoules);
        self::assertTrue($canStillAssign);
    }
}
