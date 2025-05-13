<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\TestHelper\PlanningCreator;

class InputTest extends TestCase
{
    use PlanningCreator;

    public function testBestPlanningByNrOfBatches(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput(
            [5],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 0);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(4);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanning(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput(
            [5],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(PlanningState::Failed);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $input->getBestPlanning(null));
    }

    public function testBestPlanningOnBatchGamesVersusGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput(
            [5],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 0);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 1);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningB, $input->getBestPlanning(null));
    }

    public function testBestPlanningOnGamesInARow(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $input = $this->createInput(
            [5],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $batchGamesRange = new SportRange(2, 2);
        $planningA = new Planning($input, $batchGamesRange, 1);
        $planningA->setState(PlanningState::Succeeded);
        $planningA->setNrOfBatches(5);

        $planningB = new Planning($input, $batchGamesRange, 2);
        $planningB->setState(PlanningState::Succeeded);
        $planningB->setNrOfBatches(5);

        self::assertSame($planningA, $input->getBestPlanning(null));
    }
}
