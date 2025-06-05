<?php

namespace SportsScheduler\Resource\RefereePlace;

use DateTimeImmutable;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Resource\GameCounter\GameCounterForPlace;
use SportsScheduler\Exceptions\TimeoutException;
use SportsPlanning\Game;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Planning\TimeoutConfig;

final class Service
{
    protected int $nrOfPlaces;
    private Replacer $replacer;
    private bool $throwOnTimeout;

    public function __construct(private Planning $planning)
    {
        $this->nrOfPlaces = $this->planning->getNrOfPlaces();
        $selfReferee = $planning->getConfiguration()->refereeInfo->selfRefereeInfo->selfReferee;
        $this->replacer = new Replacer($selfReferee === SelfReferee::SamePoule);
        $this->throwOnTimeout = true;
    }

    public function assign(SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): PlanningState
    {
        return $this->assignHelper($batch);
    }

    public function assignHelper(SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): PlanningState
    {
        $timeoutConfig = new TimeoutConfig();
        $nextTimeoutState = $timeoutConfig->nextTimeoutState($this->planning);
        $timeoutSeconds = $timeoutConfig->getTimeoutSeconds($this->planning->getConfiguration(), $nextTimeoutState);
        $timeoutDateTime = (new DateTimeImmutable())->add(new \DateInterval('PT' . $timeoutSeconds . 'S'));
        $this->replacer->setTimeoutDateTime($timeoutDateTime);
        $refereePlaceMap = $this->getRefereePlaceMap();
        try {
            if ($this->assignBatch($batch, $batch->getBase()->getGames(), $refereePlaceMap, $timeoutDateTime)) {
                return PlanningState::Succeeded;
            };
        } catch (TimeoutException $timeoutExc) {
            return PlanningState::TimedOut;
        }
        return PlanningState::Failed;
    }

    /**
     * @return array<string,GameCounterForPlace>
     */
    protected function getRefereePlaceMap(): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getPlaces() as $place) {
            $gameCounter = new GameCounterForPlace($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    /**
     * @param SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch
     * @param list<TogetherGame|AgainstGame> $batchGames
     * @param array<string,GameCounterForPlace> $refereePlaceMap
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        array $batchGames,
        array $refereePlaceMap,
        DateTimeImmutable $timeoutDateTime
    ): bool {
        if (count($batchGames) === 0) { // batchsuccess
            $nextBatch = $batch->getNext();
            if ($nextBatch === null) { // endsuccess
//                (new BatchOutput())->output($batch, null, null, null, true);
                return $this->equallyAssign($batch);
            }
            if ($this->throwOnTimeout && (new DateTimeImmutable()) > $timeoutDateTime) {
                throw new TimeoutException(
                    "exceeded maximum duration",
                    E_ERROR
                );
            }
            return $this->assignBatch($nextBatch, $nextBatch->getBase()->getGames(), $refereePlaceMap, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        foreach ($refereePlaceMap as $refereePlace) {
            if (!$this->isRefereePlaceAssignable($batch, $game, $refereePlace->place)) {
                continue;
            }
            $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->place, $refereePlaceMap);
            if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                return true;
            }
            // statics
            $game->setRefereePlaceUniqueIndex(null);
            $batch->removeReferee($refereePlace->place->getUniqueIndex());
        }
        return false;
    }

    protected function equallyAssign(SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): bool
    {
        return $this->replacer->replaceUnequals($this->planning, $batch->getFirst());
    }

    private function isRefereePlaceAssignable(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        AgainstGame|TogetherGame $game,
        Place $refereePlace): bool
    {
        if ($batch->getBase()->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if ($this->planning->getConfiguration()->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::SamePoule) {
            return $refereePlace->pouleNr === $game->poule->pouleNr;
        }
//        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
//            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
//            return true;
//        }
        return $refereePlace->pouleNr !== $game->poule->pouleNr;
    }

    /**
     * @param SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch
     * @param TogetherGame|AgainstGame $game
     * @param Place $assignPlace
     * @param array<string,GameCounterForPlace> $refereePlaceMap
     * @return array<string,GameCounterForPlace>
     */
    private function assignRefereePlace(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        TogetherGame|AgainstGame $game,
        Place $assignPlace,
        array $refereePlaceMap
    ): array {
        $game->setRefereePlaceUniqueIndex($assignPlace->getUniqueIndex());
        $batch->addRefereeUniqueIndex($assignPlace->getUniqueIndex());

        $newRefereePlaceMap = [];
        foreach ($refereePlaceMap as $refereePlace) {
            $newRefereePlaceCounter = new GameCounterForPlace($refereePlace->place, $refereePlace->getNrOfGames());
            if ($refereePlace->place === $assignPlace) {
                $newRefereePlaceCounter = $newRefereePlaceCounter->increment();
            }
            $newRefereePlaceMap[$newRefereePlaceCounter->getIndex()] = $newRefereePlaceCounter;

        }
        uasort(
            $newRefereePlaceMap,
            function (GameCounterForPlace $a, GameCounterForPlace $b): int {
                return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
            }
        );
        return $newRefereePlaceMap;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
        $this->replacer->disableThrowOnTimeout();
    }
}
