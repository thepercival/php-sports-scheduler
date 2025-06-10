<?php

declare(strict_types=1);

namespace SportsScheduler\Game;

use Psr\Log\LoggerInterface;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\PlanningValidity;
use SportsPlanning\Planning\TimeoutConfig;
use SportsPlanning\PlanningWithMeta;
use SportsScheduler\Resource\RefereePlaces\RefereePlaceService;
use SportsScheduler\Resource\ResourceService;

final class GameAssigner
{
    protected bool $throwOnTimeout;
    protected bool $showHighestCompletedBatchNr = false;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function assignGames(PlanningWithMeta $planningWithMeta, int $maxNrOfBatches): PlanningState
    {
        $planning = $planningWithMeta->planning;
        $games = (new PreAssignSorter())->getGames($planning, $planningWithMeta->getConfiguration());
//        (new GameOutput($this->logger))->outputGames($games);

        $resourceService = new ResourceService($planningWithMeta, $this->logger);
        if (!$this->throwOnTimeout) {
            $resourceService->disableThrowOnTimeout();
        }
        if ($this->showHighestCompletedBatchNr) {
            $resourceService->showHighestCompletedBatchNr();
        }
//        $resourceService->showHighestCompletedBatchNr();
        $state = $resourceService->assign($games, $maxNrOfBatches);
        if ($state === PlanningState::Failed || $state === PlanningState::TimedOut) {
            $planning->removeGames();
            $planningWithMeta->setState($state);
            $planningWithMeta->setNrOfBatches(0);
            if ($state === PlanningState::TimedOut) {
                $planningWithMeta->setTimeoutState((new TimeoutConfig())->nextTimeoutState($planningWithMeta));
            } else {
                $planningWithMeta->setTimeoutState(null);
            }
            return $state;
        }

        $firstBatch = $planningWithMeta->createFirstBatch();
//        (new BatchOutput())->output($firstBatch );
        if ($firstBatch instanceof SelfRefereeBatchOtherPoules || $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($planningWithMeta);
            if (!$this->throwOnTimeout) {
                $refereePlaceService->disableThrowOnTimeout();
            }
            $state = $refereePlaceService->assign($firstBatch);
            if ($state === PlanningState::Failed || $state === PlanningState::TimedOut) {
                $planning->removeGames();
                $planningWithMeta->setState($state);
                $planningWithMeta->setNrOfBatches(0);
                if ($state === PlanningState::TimedOut) {
                    $planningWithMeta->setTimeoutState((new TimeoutConfig())->nextTimeoutState($planningWithMeta));
                } else {
                    $planningWithMeta->setTimeoutState(null);
                }
                $this->logger->error('   could not assign refereeplaces (plId:' . (string)$planningWithMeta->id . ')');
                return $state;
            }
        }
        $planningWithMeta->setState(PlanningState::Succeeded);
        $planningWithMeta->setNrOfBatches($firstBatch->getLeaf()->getNumber());
        $planningWithMeta->setValidity(PlanningValidity::NOT_VALIDATED);
        $planningWithMeta->setTimeoutState(null);
        return PlanningState::Succeeded;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }

    public function showHighestCompletedBatchNr(): void
    {
        $this->showHighestCompletedBatchNr = true;
    }
}
