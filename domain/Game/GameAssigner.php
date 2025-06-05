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
use SportsScheduler\Resource\RefereePlace\Service as RefereePlaceService;
use SportsScheduler\Resource\ResourceService;

final class GameAssigner
{
    protected bool $throwOnTimeout;
    protected bool $showHighestCompletedBatchNr = false;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function assignGames(Planning $planning, int $maxNrOfBatches): PlanningState
    {
        $games = (new PreAssignSorter())->getGames($planning);
//        (new GameOutput($this->logger))->outputGames($games);

        $resourceService = new ResourceService($planning, $this->logger);
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
            $planning->setState($state);
            $planning->setNrOfBatches(0);
            if ($state === PlanningState::TimedOut) {
                $planning->setTimeoutState((new TimeoutConfig())->nextTimeoutState($planning));
            } else {
                $planning->setTimeoutState(null);
            }
            return $state;
        }

        $firstBatch = $planning->createFirstBatch();
//        (new BatchOutput())->output($firstBatch );
        if ($firstBatch instanceof SelfRefereeBatchOtherPoules || $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($planning);
            if (!$this->throwOnTimeout) {
                $refereePlaceService->disableThrowOnTimeout();
            }
            $state = $refereePlaceService->assign($firstBatch);
            if ($state === PlanningState::Failed || $state === PlanningState::TimedOut) {
                $planning->removeGames();
                $planning->setState($state);
                $planning->setNrOfBatches(0);
                if ($state === PlanningState::TimedOut) {
                    $planning->setTimeoutState((new TimeoutConfig())->nextTimeoutState($planning));
                } else {
                    $planning->setTimeoutState(null);
                }
                $this->logger->error('   could not assign refereeplaces (plId:' . ($planning->id ?? '') . ')');
                return $state;
            }
        }
        $planning->setState(PlanningState::Succeeded);
        $planning->setNrOfBatches($firstBatch->getLeaf()->getNumber());
        $planning->setValidity(PlanningValidity::NOT_VALIDATED);
        $planning->setTimeoutState(null);
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
