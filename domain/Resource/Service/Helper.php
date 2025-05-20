<?php

namespace SportsScheduler\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoule;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Planning\BatchGamesType;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Filter as PlanningFilter;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Referee\PlanningRefereeInfo;

class Helper
{
    protected int $totalNrOfGames;
    protected int|null $maxNrOfBatches = null;
    public readonly PlanningPouleStructure $planningPouleStructure;

    public function __construct(
        protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->planningPouleStructure = $planning->getInput()->createPlanningPouleStructure();
        $this->totalNrOfGames = $this->planningPouleStructure->calculateNrOfGames();

        $this->initMaxNrOfBatches();
    }

    private function initMaxNrOfBatches(): void
    {
        try {
            if ($this->planning->getType() === PlanningType::BatchGames) {
                // -1 because needs to be less nrOfBatches
                $this->maxNrOfBatches = $this->planning->getInput()->getBestPlanning(null)->getNrOfBatches() - 1;
            } else {
                $planningFilter = new PlanningFilter( null, null,
                    $this->planning->getNrOfBatchGames(), 0);
                $batchGamePlanning = $this->planning->getInput()->getPlanning($planningFilter);
                if ($batchGamePlanning !== null) {
                    $this->maxNrOfBatches = $batchGamePlanning->getNrOfBatches();
                }
            }
        } catch (NoBestPlanningException $e) {
        }
    }

    /**
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule $previousBatch
     * @param array<TogetherGame|AgainstGame> $gamesForBatchTmp
     * @param PlanningCounters $infoToAssign
     */
    public function sortGamesForNextBatch(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule $previousBatch,
        array &$gamesForBatchTmp,
        PlanningCounters $infoToAssign
    ): void {
        uasort(
            $gamesForBatchTmp,
            function (TogetherGame|AgainstGame $gameA, TogetherGame|AgainstGame $gameB) use (
                $previousBatch,
                $infoToAssign
            ): int {
                $mostToAssignA = $this->getMostToAssign($gameA, $infoToAssign);
                $mostToAssignB = $this->getMostToAssign($gameB, $infoToAssign);
                if ($mostToAssignB !== $mostToAssignA) {
                    return $mostToAssignB - $mostToAssignA;
                }
                $sumToAssignA = $this->getSumToAssign($gameA, $infoToAssign);
                $sumToAssignB = $this->getSumToAssign($gameB, $infoToAssign);
                if ($sumToAssignB !== $sumToAssignA) {
                    return $sumToAssignB - $sumToAssignA;
                }
                $amountA = count(
                    $gameA->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                $amountB = count(
                    $gameB->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                return $amountB - $amountA;
            }
        );
    }

    protected function getMostToAssign(AgainstGame|TogetherGame $game, PlanningCounters $assignedInfo): int
    {
        $mosts = array_map( function (Place $place) use ($assignedInfo): int {
            return $assignedInfo->getNrOfGames($place);
        }, $game->getPoulePlaces()->toArray() );
        return count($mosts) > 0 ? max($mosts) : 0;
    }

    protected function getSumToAssign(AgainstGame|TogetherGame $game, PlanningCounters $assignedInfo): int
    {
        return array_sum(
            array_map( function (Place $place) use ($assignedInfo): int {
                return $assignedInfo->getNrOfGames($place);
            }, $game->getPoulePlaces()->toArray() )
        );
    }

    /**
     * @param int $batchNumber
     * @param PlanningCounters $unassignedPlanningCounters
     * @return bool
     */
    public function canGamesBeAssigned(int $batchNumber, PlanningCounters $unassignedPlanningCounters): bool
    {
        $maxNrOfBatches = $this->maxNrOfBatches === null ? $this->planning->getMaxNrOfBatches() : $this->maxNrOfBatches;
        $maxNrOfBatchesToGo = $maxNrOfBatches - $batchNumber;
        if ($this->willMaxNrOfBatchesBeExceeded($maxNrOfBatchesToGo, $unassignedPlanningCounters)) {
            return false;
        }
        if (
            (
                $unassignedPlanningCounters->getNrOfGames() < $this->planning->getMinNrOfBatchGames()
                && $this->planning->getBatchGamesType() === BatchGamesType::RangeIsZero
            )
            ||
            $this->willMinNrOfBatchGamesBeReached($unassignedPlanningCounters)) {
            return true;
        }
        return false;
    }


    public function willMaxNrOfBatchesBeExceeded(int $maxNrOfBatchesToGo, PlanningCounters $unassignedPlanningCounters): bool
    {
        if ($this->willMaxNrOfBatchesBeExceededForSports($maxNrOfBatchesToGo, $unassignedPlanningCounters)) {
            return true;
        }
        if ($this->willMaxNrOfBatchesBeExceededForPlaces($maxNrOfBatchesToGo, $unassignedPlanningCounters)) {
            return true;
        }
        return false;
    }

    public function willMaxNrOfBatchesBeExceededForSports(
        int $maxNrOfBatchesToGo, PlanningCounters $unassignedPlanningCounters): bool
    {
        $maxUnassignedNrOfBatchGamesAllSports = 0;
        foreach ($unassignedPlanningCounters->getCountersForSports() as $countersForSport) {
            $maxUnassignedNrOfBatchGames = (new SimCalculator())->calculateMaxSimNrOfSportGames(
                $this->planningPouleStructure->pouleStructure,
                $countersForSport->sportWithNrOfFields,
                $this->planningPouleStructure->refereeInfo
            );
            if ($maxUnassignedNrOfBatchGames > $this->planning->getMaxNrOfBatchGames()) {
                $maxUnassignedNrOfBatchGames = $this->planning->getMaxNrOfBatchGames();
            }

            $minNrOfBatches = (int)ceil($countersForSport->getNrOfGames() / $maxUnassignedNrOfBatchGames);
            if ($minNrOfBatches > $maxNrOfBatchesToGo) {
                return true;
            }
//            $maxUnassignedNrOfBatchGames = (int)ceil($countersForSport->getNrOfGames() / $minNrOfBatches);
            $maxUnassignedNrOfBatchGamesAllSports += $maxUnassignedNrOfBatchGames;
        }
//        if ($maxNrOfBatchGamesAllSports < $this->planning->getMinNrOfBatchGames()) {
//            return true;
//        }
        // $maxNrOfBatchGamesAllSports = $simCalculator->getMaxNrOfGamesPerBatch($infoToAssign);

        if ($maxUnassignedNrOfBatchGamesAllSports > $this->planning->getMaxNrOfBatchGames()) {
            $maxUnassignedNrOfBatchGamesAllSports = $this->planning->getMaxNrOfBatchGames();
        }
        $minNrOfBatches = (int)ceil($unassignedPlanningCounters->getNrOfGames() / $maxUnassignedNrOfBatchGamesAllSports);
        return $minNrOfBatches > $maxNrOfBatchesToGo;
    }

    public function willMaxNrOfBatchesBeExceededForPlaces(
        int $maxNrOfBatchesToGo, PlanningCounters $unassignedPlanningCounters): bool
    {
//        if ($infoToAssign->isEmpty()) {
//            return false;
//        }
        foreach ($unassignedPlanningCounters->getPlaceGameCounters() as $placeGameCounter) {
            if ($placeGameCounter->getNrOfGames() > $maxNrOfBatchesToGo) {
                return true;
            }
        }

        // //////////////////////
        // per poule en sport kijken als het nog gehaald kunnen worden
        foreach ($unassignedPlanningCounters->getCountersForSports() as $countersForSport) {
            foreach ($countersForSport->getUniquePlacesCounters() as $uniquePlacesCounter) {
                // all pouleplaces
                $nrOfPlaces = count($uniquePlacesCounter->getPoule()->getPlaces());

                $maxNrOfBatchGames = (new SimCalculator())->calculateMaxSimNrOfSportGames(
                    new PouleStructure($nrOfPlaces),
                    $countersForSport->sportWithNrOfFields,
                    $this->planningPouleStructure->refereeInfo);

                $nrOfBatchesNeeded = (int)ceil($uniquePlacesCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }

                $selfRefereeInfo = new SelfRefereeInfo(SelfReferee::Disabled);
                $maxNrOfBatchGames = (new SimCalculator())->calculateMaxSimNrOfSportGames(
                    new PouleStructure($uniquePlacesCounter->getNrOfDistinctPlacesAssigned()),
                    $countersForSport->sportWithNrOfFields,
                    new PlanningRefereeInfo($selfRefereeInfo)
                );
                $nrOfBatchesNeeded = (int)ceil($uniquePlacesCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param PlanningCounters $assignPlanningCounters
     * @return bool
     */
    public function willMinNrOfBatchGamesBeReached(PlanningCounters $unassignedPlanningCounters): bool
    {
        // $sportInfosWithMoreNrOfBatchesNeeded = $this->getSportInfosWithMoreNrOfBatchesNeeded($sportInfoMap);
        $simCalculator = new SimCalculator();
        $maxNrOfSimGames = $simCalculator->calculateMaxSimNrOfGames(
            $this->planningPouleStructure->pouleStructure,
            $this->planningPouleStructure->createSportsWithNrOfFields(),
            $this->planningPouleStructure->refereeInfo
        );
        if ($maxNrOfSimGames < $this->planning->getMinNrOfBatchGames()) {
            return false;
        }

        $sortedCountersForSports = $this->getCountersForSportsSortedByNrOfSimGames($unassignedPlanningCounters);
        // $sortedSportInfos = $sportInfoMap->getSportInfoMap();

        $nrOfSimultaneousGames = 0;
        while ($nrOfSimultaneousGames < $this->planning->getMinNrOfBatchGames()) {
            $countersForSport = array_shift($sortedCountersForSports);
            if ($countersForSport === null) {
                return false;
            }
            $nrOfSimultaneousSportGames = $simCalculator->calculateMaxSimNrOfSportGames(
                $this->planningPouleStructure->pouleStructure,
                $countersForSport->sportWithNrOfFields,
                $this->planningPouleStructure->refereeInfo
            );
            $nrOfSimultaneousGames += $nrOfSimultaneousSportGames;

            if ($countersForSport->getNrOfGames() >= $nrOfSimultaneousSportGames) {
                $minNrOfBatchesForSportNeeded = (int)floor($countersForSport->getNrOfGames() / $nrOfSimultaneousSportGames);
                // $maxNrOfGamesPerBatchLimit = (int)ceil($infoToAssign->getNrOfGames() / $minNrOfBatchesForSportNeeded);
                $maxNrOfGamesPerBatchLimit = $countersForSport->getNrOfGames() / $minNrOfBatchesForSportNeeded;
                if ($maxNrOfGamesPerBatchLimit < $this->planning->getMinNrOfBatchGames()) {
                    return false;
                }
            }
        }
        if ($this->planning->getBatchGamesType() === BatchGamesType::RangeIsOneOrMore) {
            return $unassignedPlanningCounters->getNrOfGames() >= $this->planning->getMinNrOfBatchGames();
        }

        $minNrOfBatchesForGamesPerPlaceNeeded = $this->getMinNrOfBatchesForGamesPerPlaceNeeded($unassignedPlanningCounters);

        $restNrOfGames = $unassignedPlanningCounters->getNrOfGames() % $this->planning->getMinNrOfBatchGames();
        $roundedNrOfGames = $unassignedPlanningCounters->getNrOfGames() - $restNrOfGames;
        $maxNrOfRestGames = $this->totalNrOfGames % $this->planning->getMinNrOfBatchGames();
        if ($restNrOfGames <= $maxNrOfRestGames) {
            $roundedNrOfGames += $this->planning->getMinNrOfBatchGames();
        }

        $minNrOfBatchGamesPerPlaceNeeded = (int)floor($roundedNrOfGames / $minNrOfBatchesForGamesPerPlaceNeeded);
        if ($minNrOfBatchGamesPerPlaceNeeded >= $this->planning->getMinNrOfBatchGames()) {
            return true;
        }
        return false;
    }

    /**
     * @param PlanningCounters $planningCounters
     * @return list<NrOfGamesAndUniquePlacesCounterForSport>
     */
    public function getCountersForSportsSortedByNrOfSimGames(PlanningCounters $planningCounters): array
    {
        $countersForSports = $planningCounters->getCountersForSports();
        uasort($countersForSports, function (
            NrOfGamesAndUniquePlacesCounterForSport $a, NrOfGamesAndUniquePlacesCounterForSport $b): int {
            $simCalculator = new SimCalculator();
            $nrOfSimGamesA = $simCalculator->calculateMaxSimNrOfSportGames(
                $this->planningPouleStructure->pouleStructure,
                $a->sportWithNrOfFields,
                $this->planningPouleStructure->refereeInfo
            );
            $nrOfSimGamesB = $simCalculator->calculateMaxSimNrOfSportGames(
                $this->planningPouleStructure->pouleStructure,
                $b->sportWithNrOfFields,
                $this->planningPouleStructure->refereeInfo
            );
            return $nrOfSimGamesB - $nrOfSimGamesA;
        });
        return array_values($countersForSports);
    }

    protected function getMinNrOfBatchesForGamesPerPlaceNeeded(PlanningCounters $assignPlanningCounters): int
    {
        $minNrOfBatchesNeeded = 0;
        foreach ($assignPlanningCounters->getPlaceGameCounters() as $placeGameCounter) {
            if ($placeGameCounter->getNrOfGames() > $minNrOfBatchesNeeded) {
                $minNrOfBatchesNeeded = $placeGameCounter->getNrOfGames();
            }
        }
        return $minNrOfBatchesNeeded;
    }

    /**
     * @param int $batchNumber
     * @param PlanningCounters $assignPlanningCounters
     * @return list<Place>
     */
    public function getRequiredPlaces(int $batchNumber, PlanningCounters $assignPlanningCounters): array
    {
        $maxNrOfBatchesToGo = $this->planning->getMaxNrOfBatches() - $batchNumber;
        $requiredPlaces = [];
        foreach ($assignPlanningCounters->getPlaceGameCounters() as $placeGameCounter) {
            if ($placeGameCounter->getNrOfGames() >= $maxNrOfBatchesToGo) {
                $requiredPlaces[] = $placeGameCounter->getPlace();
            }
        }
        return $requiredPlaces;
    }



//    }

    // AllInOneGame
    //    public function getMaxNrOfGamesSimultaneouslyPossible(SelfRefereeInfo $selfRefereeInfo): int {
//        return 1;
//    }

    // Against
//    public function getMaxNrOfGamesSimultaneouslyPossible(SelfRefereeInfo $refereeInfo): int {

//    }
}
