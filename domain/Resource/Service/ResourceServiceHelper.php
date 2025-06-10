<?php

namespace SportsScheduler\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Planning\BatchGamesType;
use SportsPlanning\Planning;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\PlanningWithMeta;

final class ResourceServiceHelper
{
    protected int $totalNrOfGames;
    public readonly PlanningPouleStructure $planningPouleStructure;

    public function __construct(protected PlanningWithMeta $planningWithMeta, protected LoggerInterface $logger)
    {
        $this->planningPouleStructure = $planningWithMeta->getConfiguration()->createPlanningPouleStructure();
        $this->totalNrOfGames = $this->planningPouleStructure->calculateNrOfGames();
    }

    /**
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $previousBatch
     * @param array<TogetherGame|AgainstGame> $gamesForBatchTmp
     * @param PlanningCounters $infoToAssign
     */
    public function sortGamesForNextBatch(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $previousBatch,
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
                $pouleA = $this->planningWithMeta->planning->getPoule($gameA->pouleNr);
                $amountA = count(
                    array_filter($pouleA->getPlaces($gameA), function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                $pouleB = $this->planningWithMeta->planning->getPoule($gameB->pouleNr);
                $amountB = count(
                    array_filter($pouleB->getPlaces($gameB), function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                return $amountB - $amountA;
            }
        );
    }

    protected function getMostToAssign(AgainstGame|TogetherGame $game, PlanningCounters $assignedInfo): int
    {
        $poule = $this->planningWithMeta->planning->getPoule($game->pouleNr);
        $mosts = array_map( function (Place $place) use ($assignedInfo): int {
            return $assignedInfo->getNrOfGames($place);
        }, $poule->getPlaces($game) );
        return count($mosts) > 0 ? max($mosts) : 0;
    }

    protected function getSumToAssign(AgainstGame|TogetherGame $game, PlanningCounters $assignedInfo): int
    {
        $poule = $this->planningWithMeta->planning->getPoule($game->pouleNr);
        return array_sum(
            array_map( function (Place $place) use ($assignedInfo): int {
                return $assignedInfo->getNrOfGames($place);
            }, $poule->getPlaces($game) )
        );
    }

    public function canGamesBeAssigned(int $batchNumber, int $maxNrOfBatches, PlanningCounters $unassignedPlanningCounters): bool
    {
        $maxNrOfBatchesToGo = $maxNrOfBatches - $batchNumber;
        if ($this->willMaxNrOfBatchesBeExceeded($maxNrOfBatchesToGo, $unassignedPlanningCounters)) {
            return false;
        }
        if (
            (
                $unassignedPlanningCounters->getNrOfGames() < $this->planningWithMeta->minNrOfBatchGames
                && $this->planningWithMeta->getBatchGamesType() === BatchGamesType::RangeIsZero
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
            if ($maxUnassignedNrOfBatchGames > $this->planningWithMeta->maxNrOfBatchGames) {
                $maxUnassignedNrOfBatchGames = $this->planningWithMeta->maxNrOfBatchGames;
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

        if ($maxUnassignedNrOfBatchGamesAllSports > $this->planningWithMeta->maxNrOfBatchGames) {
            $maxUnassignedNrOfBatchGamesAllSports = $this->planningWithMeta->maxNrOfBatchGames;
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
                $nrOfPlaces = count($uniquePlacesCounter->getPoule()->places);

                $maxNrOfBatchGames = (new SimCalculator())->calculateMaxSimNrOfSportGames(
                    new PouleStructure([$nrOfPlaces]),
                    $countersForSport->sportWithNrOfFields,
                    $this->planningPouleStructure->refereeInfo);

                $nrOfBatchesNeeded = (int)ceil($uniquePlacesCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }

                $maxNrOfBatchGames = (new SimCalculator())->calculateMaxSimNrOfSportGames(
                    new PouleStructure([$uniquePlacesCounter->getNrOfDistinctPlacesAssigned()]),
                    $countersForSport->sportWithNrOfFields,
                    null
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
        if ($maxNrOfSimGames < $this->planningWithMeta->minNrOfBatchGames) {
            return false;
        }

        $sortedCountersForSports = $this->getCountersForSportsSortedByNrOfSimGames($unassignedPlanningCounters);
        // $sortedSportInfos = $sportInfoMap->getSportInfoMap();

        $nrOfSimultaneousGames = 0;
        while ($nrOfSimultaneousGames < $this->planningWithMeta->minNrOfBatchGames) {
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
                if ($maxNrOfGamesPerBatchLimit < $this->planningWithMeta->minNrOfBatchGames) {
                    return false;
                }
            }
        }
        if ($this->planningWithMeta->getBatchGamesType() === BatchGamesType::RangeIsOneOrMore) {
            return $unassignedPlanningCounters->getNrOfGames() >= $this->planningWithMeta->minNrOfBatchGames;
        }

        $minNrOfBatchesForGamesPerPlaceNeeded = $this->getMinNrOfBatchesForGamesPerPlaceNeeded($unassignedPlanningCounters);

        $restNrOfGames = $unassignedPlanningCounters->getNrOfGames() % $this->planningWithMeta->minNrOfBatchGames;
        $roundedNrOfGames = $unassignedPlanningCounters->getNrOfGames() - $restNrOfGames;
        $maxNrOfRestGames = $this->totalNrOfGames % $this->planningWithMeta->minNrOfBatchGames;
        if ($restNrOfGames <= $maxNrOfRestGames) {
            $roundedNrOfGames += $this->planningWithMeta->minNrOfBatchGames;
        }

        $minNrOfBatchGamesPerPlaceNeeded = (int)floor($roundedNrOfGames / $minNrOfBatchesForGamesPerPlaceNeeded);
        if ($minNrOfBatchGamesPerPlaceNeeded >= $this->planningWithMeta->minNrOfBatchGames) {
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
        $maxNrOfBatchesToGo = $this->calculateMaxNrOfBatches() - $batchNumber;
        $requiredPlaces = [];
        foreach ($assignPlanningCounters->getPlaceGameCounters() as $placeGameCounter) {
            if ($placeGameCounter->getNrOfGames() >= $maxNrOfBatchesToGo) {
                $requiredPlaces[] = $placeGameCounter->place;
            }
        }
        return $requiredPlaces;
    }


    private function calculateMaxNrOfBatches(): int
    {
        $totalNrOfGames = $this->planningWithMeta->getConfiguration()->createPlanningPouleStructure()->calculateNrOfGames();
        return (int)ceil($totalNrOfGames / $this->planningWithMeta->minNrOfBatchGames);
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
