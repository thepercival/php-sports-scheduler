<?php

namespace SportsScheduler\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Input;
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
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch
     * @param array<TogetherGame|AgainstGame> $gamesForBatchTmp
     */
    public function sortGamesForNextBatch(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch,
        array &$gamesForBatchTmp,
        AssignPlanningCounters $infoToAssign
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

    protected function getMostToAssign(AgainstGame|TogetherGame $game, AssignPlanningCounters $assignedInfo): int
    {
        $mosts = array_map( function (Place $place) use ($assignedInfo): int {
            return $assignedInfo->getNrOfGames($place);
        }, $game->getPoulePlaces()->toArray() );
        return count($mosts) > 0 ? max($mosts) : 0;
    }

    protected function getSumToAssign(AgainstGame|TogetherGame $game, AssignPlanningCounters $assignedInfo): int
    {
        return array_sum(
            array_map( function (Place $place) use ($assignedInfo): int {
                return $assignedInfo->getNrOfGames($place);
            }, $game->getPoulePlaces()->toArray() )
        );
    }

    /**
     * @param int $batchNumber
     * @param AssignPlanningCounters $infoToAssign
     * @return bool
     */
    public function canGamesBeAssigned(int $batchNumber, AssignPlanningCounters $infoToAssign): bool
    {
        $maxNrOfBatches = $this->maxNrOfBatches === null ? $this->planning->getMaxNrOfBatches() : $this->maxNrOfBatches;
        $maxNrOfBatchesToGo = $maxNrOfBatches - $batchNumber;
        if ($this->willMaxNrOfBatchesBeExceeded($maxNrOfBatchesToGo, $infoToAssign)) {
            return false;
        }
        if (
            (
                $infoToAssign->getNrOfGames() < $this->planning->getMinNrOfBatchGames()
                && $this->planning->getBatchGamesType() === BatchGamesType::RangeIsZero
            )
            ||
            $this->willMinNrOfBatchGamesBeReached($infoToAssign)) {
            return true;
        }
        return false;
    }


    public function willMaxNrOfBatchesBeExceeded(int $maxNrOfBatchesToGo, AssignPlanningCounters $infoToAssign): bool
    {
        if ($this->willMaxNrOfBatchesBeExceededForSports($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        if ($this->willMaxNrOfBatchesBeExceededForPlaces($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        return false;
    }

    public function willMaxNrOfBatchesBeExceededForSports(
        int $maxNrOfBatchesToGo, AssignPlanningCounters $assignPlanningCounters): bool
    {
        $simCalculator = new SimCalculator();

        $maxNrOfBatchGamesAllSports = 0;
        foreach ($assignPlanningCounters->getCountersForSports() as $countersForSport) {
            $maxNrOfBatchGames = $simCalculator->calculateMaxSimNrOfSportGames(
                $this->planningPouleStructure->pouleStructure,
                $countersForSport->sportWithNrOfFields,
                $this->planningPouleStructure->refereeInfo
            );
            if ($maxNrOfBatchGames > $this->planning->getMaxNrOfBatchGames()) {
                $maxNrOfBatchGames = $this->planning->getMaxNrOfBatchGames();
            }
            $minNrOfBatches = (int)ceil($countersForSport->getNrOfGames() / $maxNrOfBatchGames);
            if ($minNrOfBatches > $maxNrOfBatchesToGo) {
                return true;
            }
            $maxNrOfBatchGames = (int)ceil($countersForSport->getNrOfGames() / $minNrOfBatches);
            $maxNrOfBatchGamesAllSports += $maxNrOfBatchGames;
        }
        if ($maxNrOfBatchGamesAllSports < $this->planning->getMinNrOfBatchGames()) {
            return true;
        }

        if ($maxNrOfBatchGamesAllSports > $this->planning->getMaxNrOfBatchGames()) {
            $maxNrOfBatchGamesAllSports = $this->planning->getMaxNrOfBatchGames();
        }
        $minNrOfBatches = (int)ceil($assignPlanningCounters->getNrOfGames() / $maxNrOfBatchGamesAllSports);
        return $minNrOfBatches > $maxNrOfBatchesToGo;
    }

    public function willMaxNrOfBatchesBeExceededForPlaces(
        int $maxNrOfBatchesToGo, AssignPlanningCounters $assignPlanningCounters): bool
    {
//        if ($infoToAssign->isEmpty()) {
//            return false;
//        }
        foreach ($assignPlanningCounters->getPlaceGameCounters() as $placeGameCounter) {
            if ($placeGameCounter->getNrOfGames() > $maxNrOfBatchesToGo) {
                return true;
            }
        }

        // //////////////////////
        // per poule en sport kijken als het nog gehaald kunnen worden
        foreach ($assignPlanningCounters->getCountersForSports() as $countersForSport) {
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
     * @param AssignPlanningCounters $assignPlanningCounters
     * @return bool
     */
    public function willMinNrOfBatchGamesBeReached(AssignPlanningCounters $assignPlanningCounters): bool
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

        $sortedCountersForSports = $this->getCountersForSportsSortedByNrOfSimGames($assignPlanningCounters);
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
            return $countersForSport->getNrOfGames() >= $this->planning->getMinNrOfBatchGames();
        }

        $minNrOfBatchesForGamesPerPlaceNeeded = $this->getMinNrOfBatchesForGamesPerPlaceNeeded($assignPlanningCounters);

        $restNrOfGames = $assignPlanningCounters->getNrOfGames() % $this->planning->getMinNrOfBatchGames();
        $roundedNrOfGames = $assignPlanningCounters->getNrOfGames() - $restNrOfGames;
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
     * @param AssignPlanningCounters $assignPlanningCounters
     * @return list<NrOfGamesAndUniquePlacesCounterForSport>
     */
    public function getCountersForSportsSortedByNrOfSimGames(AssignPlanningCounters $assignPlanningCounters): array
    {
        $countersForSports = $assignPlanningCounters->getCountersForSports();
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

    protected function getMinNrOfBatchesForGamesPerPlaceNeeded(AssignPlanningCounters $assignPlanningCounters): int
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
     * @param AssignPlanningCounters $assignPlanningCounters
     * @return list<Place>
     */
    public function getRequiredPlaces(int $batchNumber, AssignPlanningCounters $assignPlanningCounters): array
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
