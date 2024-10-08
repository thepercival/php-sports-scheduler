<?php

namespace SportsScheduler\Resource\RefereePlace;

use DateTimeImmutable;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsScheduler\Exceptions\TimeoutException;
use SportsPlanning\Game;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutConfig;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;

class Service
{
    protected int $nrOfPlaces;
    private Replacer $replacer;
    private bool $throwOnTimeout;

    public function __construct(private Planning $planning)
    {
        $this->nrOfPlaces = $this->planning->getInput()->getNrOfPlaces();
        $this->replacer = new Replacer($planning->getInput()->getSelfReferee() === SelfReferee::SamePoule);
        $this->throwOnTimeout = true;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): PlanningState
    {
        return $this->assignHelper($batch);
    }

    public function assignHelper(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): PlanningState
    {
        $timeoutConfig = new TimeoutConfig();
        $nextTimeoutState = $timeoutConfig->nextTimeoutState($this->planning);
        $timeoutSeconds = $timeoutConfig->getTimeoutSeconds($this->planning->getInput(), $nextTimeoutState);
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
     * @return array<string,PlaceGameCounter>
     */
    protected function getRefereePlaceMap(): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getInput()->getPlaces() as $place) {
            $gameCounter = new PlaceGameCounter($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    /**
     * @param SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
     * @param list<TogetherGame|AgainstGame> $batchGames
     * @param array<string,PlaceGameCounter> $refereePlaceMap
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
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
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace->getPlace())) {
                $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->getPlace(), $refereePlaceMap);
                if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                    return true;
                }
                // statics
                $game->setRefereePlace(null);
                $batch->removeReferee($refereePlace->getPlace());
            }
        }
        return false;
    }

    protected function equallyAssign(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): bool
    {
        return $this->replacer->replaceUnequals($this->planning, $batch->getFirst());
    }

    private function isRefereePlaceAssignable(
        SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        AgainstGame|TogetherGame $game,
        Place $refereePlace): bool
    {
        if ($batch->getBase()->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if ($this->planning->getInput()->getSelfReferee() === SelfReferee::SamePoule) {
            return $refereePlace->getPoule() === $game->getPoule();
        }
//        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
//            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
//            return true;
//        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    /**
     * @param SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
     * @param TogetherGame|AgainstGame $game
     * @param Place $assignPlace
     * @param array<string,PlaceGameCounter> $refereePlaceMap
     * @return array<string,PlaceGameCounter>
     */
    private function assignRefereePlace(
        SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        TogetherGame|AgainstGame $game,
        Place $assignPlace,
        array $refereePlaceMap
    ): array {
        $game->setRefereePlace($assignPlace);
        $batch->addReferee($assignPlace);

        $newRefereePlaceMap = [];
        foreach ($refereePlaceMap as $refereePlace) {
            $place = $refereePlace->getPlace();
            $newRefereePlaceCounter = new PlaceGameCounter($place, $refereePlace->getNrOfGames());
            if ($place === $assignPlace) {
                $newRefereePlaceCounter = $newRefereePlaceCounter->increment();
            }
            $newRefereePlaceMap[$newRefereePlaceCounter->getIndex()] = $newRefereePlaceCounter;

        }
        uasort(
            $newRefereePlaceMap,
            function (PlaceGameCounter $a, PlaceGameCounter $b): int {
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
