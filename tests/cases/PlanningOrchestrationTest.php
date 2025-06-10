<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
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
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planning = Planning::fromConfiguration($configuration);
        $batchGamesRange = new SportRange(2, 2);

        $planningAWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planning);
        $planningAWithMeta->setState(PlanningState::Succeeded);
        $planningAWithMeta->setNrOfBatches(5);

        $planningBWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planning);
        $planningBWithMeta->setState(PlanningState::Succeeded);
        $planningBWithMeta->setNrOfBatches(4);

        self::assertSame($planningBWithMeta, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planning = Planning::fromConfiguration($configuration);
        $batchGamesRange = new SportRange(2, 2);

        $planningAWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planning);
        $planningAWithMeta->setState(PlanningState::Succeeded);
        $planningAWithMeta->setNrOfBatches(5);

        $planningBWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planning);
        $planningBWithMeta->setState(PlanningState::Failed);
        $planningBWithMeta->setNrOfBatches(5);

        self::assertSame($planningAWithMeta, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planning = Planning::fromConfiguration($configuration);
        $batchGamesRange = new SportRange(2, 2);

        $planningAWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 0, $planning);
        $planningAWithMeta->setState(PlanningState::Succeeded);
        $planningAWithMeta->setNrOfBatches(5);

        $planningBWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planning);
        $planningBWithMeta->setState(PlanningState::Succeeded);
        $planningBWithMeta->setNrOfBatches(5);

        self::assertSame($planningBWithMeta, $orchestration->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planning = Planning::fromConfiguration($configuration);
        $batchGamesRange = new SportRange(2, 2);

        $planningAWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 1, $planning);
        $planningAWithMeta->setState(PlanningState::Succeeded);
        $planningAWithMeta->setNrOfBatches(5);

        $planningBWithMeta = new PlanningWithMeta($orchestration, $batchGamesRange, 2, $planning);
        $planningBWithMeta->setState(PlanningState::Succeeded);
        $planningBWithMeta->setNrOfBatches(5);

        self::assertSame($planningAWithMeta, $orchestration->getBestPlanning(null));
    }
}
