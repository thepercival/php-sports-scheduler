<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\TestHelper\PlanningCreator;

final class PlanningOrchestrationTest extends TestCase
{
    use PlanningCreator;

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration(
            $this->createConfiguration(
                [5],
                $sportsWithNrOfFieldsAndNrOfCycles,
                new PlanningRefereeInfo()
            )
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 0);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration(
            $this->createConfiguration(
                [5],
                $sportsWithNrOfFieldsAndNrOfCycles,
                new PlanningRefereeInfo()
            )
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 1);
        $planningB->setState(PlanningState::Failed);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration(
            $this->createConfiguration(
                [5],
                $sportsWithNrOfFieldsAndNrOfCycles,
                new PlanningRefereeInfo()
            )
        );

        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 1);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $orchestration = new PlanningOrchestration(
            $this->createConfiguration(
                [5],
                $sportsWithNrOfFieldsAndNrOfCycles,
                new PlanningRefereeInfo()
            )
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($orchestration, $batchGamesRange, 1);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($orchestration, $batchGamesRange, 2);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $orchestration->getBestPlanning(null));
    }
}
