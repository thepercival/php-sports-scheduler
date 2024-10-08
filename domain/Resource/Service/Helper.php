<?php

namespace SportsScheduler\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Place;
use SportsPlanning\Planning\BatchGamesType;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Filter as PlanningFilter;

class Helper
{
    protected bool $balancedStructure;
    protected int $totalNrOfGames;
    protected int|null $maxNrOfBatches = null;
    protected Input $input;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->input = $planning->getInput();
        $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();

        $sportVariants = $this->input->createSportVariants();
        $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);

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
        InfoToAssign $infoToAssign
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

    protected function getMostToAssign(AgainstGame|TogetherGame $game, InfoToAssign $infoToAssign): int
    {
        $mosts = array_map( function (Place $place) use ($infoToAssign): int {
            return $infoToAssign->getPlaceInfoMap()[$place->getUniqueIndex()]->getNrOfGames();
        }, $game->getPoulePlaces()->toArray() );
        return count($mosts) > 0 ? max($mosts) : 0;
    }

    protected function getSumToAssign(AgainstGame|TogetherGame $game, InfoToAssign $infoToAssign): int
    {
        return array_sum(
            array_map( function (Place $place) use ($infoToAssign): int {
                return $infoToAssign->getPlaceInfoMap()[$place->getUniqueIndex()]->getNrOfGames();
            }, $game->getPoulePlaces()->toArray() )
        );
    }

    /**
     * @param int $batchNumber
     * @param InfoToAssign $infoToAssign
     * @return bool
     */
    public function canGamesBeAssigned(int $batchNumber, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return true;
        }
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


    public function willMaxNrOfBatchesBeExceeded(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($this->willMaxNrOfBatchesBeExceededForSports($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        if ($this->willMaxNrOfBatchesBeExceededForPlaces($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        return false;
    }

    public function willMaxNrOfBatchesBeExceededForSports(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return false;
        }

        $simCalculator = new SimCalculator($this->input);

//        $maxNrOfBatchGamesAllSports = 0;
        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            $maxNrOfBatchGames = $simCalculator->getMaxNrOfSimultaneousSportGames($sportInfo);
            if ($maxNrOfBatchGames > $this->planning->getMaxNrOfBatchGames()) {
                $maxNrOfBatchGames = $this->planning->getMaxNrOfBatchGames();
            }
            $minNrOfBatches = (int)ceil($sportInfo->getNrOfGames() / $maxNrOfBatchGames);
            if ($minNrOfBatches > $maxNrOfBatchesToGo) {
                return true;
            }
            // $maxNrOfBatchGames = (int)ceil($sportInfo->getNrOfGames() / $minNrOfBatches);
//            $maxNrOfBatchGamesAllSports += $maxNrOfBatchGames;
        }
//        if ($maxNrOfBatchGamesAllSports < $this->planning->getMinNrOfBatchGames()) {
//            return true;
//        }

        $maxNrOfBatchGamesAllSports = $simCalculator->getMaxNrOfGamesPerBatch($infoToAssign);
        if ($maxNrOfBatchGamesAllSports > $this->planning->getMaxNrOfBatchGames()) {
            $maxNrOfBatchGamesAllSports = $this->planning->getMaxNrOfBatchGames();
        }
        $minNrOfBatches = (int)ceil($infoToAssign->getNrOfGames() / $maxNrOfBatchGamesAllSports);
        return $minNrOfBatches > $maxNrOfBatchesToGo;
    }

    public function willMaxNrOfBatchesBeExceededForPlaces(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return false;
        }
        foreach ($infoToAssign->getPlaceInfoMap() as $placeInfo) {
            if ($placeInfo->getNrOfGames() > $maxNrOfBatchesToGo) {
                return true;
            }
        }

        $simCalculator = new SimCalculator($this->input);
        // //////////////////////
        // per poule en sport kijken als het nog gehaald kunnen worden
        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            foreach ($sportInfo->getUniquePlacesCounters() as $uniquePlacesCounter) {
                // all pouleplaces
                $nrOfPlaces = count($uniquePlacesCounter->getPoule()->getPlaces());
                $variantWithNrOfPlaces = (new VariantCreator())->createWithNrOfPlaces($nrOfPlaces, $sportInfo->getVariant());
                $maxNrOfBatchGames = $simCalculator->getMaxNrOfGamesSimultaneously(
                    $variantWithNrOfPlaces, $this->input->getRefereeInfo()->selfRefereeInfo);

                $nrOfBatchesNeeded = (int)ceil($uniquePlacesCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }

                // only assigned places
                $nrOfPlaces = $uniquePlacesCounter->getNrOfDistinctPlacesAssigned();
                $variantWithNrOfPlaces2 = (new VariantCreator())->createWithNrOfPlaces($nrOfPlaces, $sportInfo->getVariant());
                $selfRefereeInfo = new SelfRefereeInfo(SelfReferee::Disabled);
                $maxNrOfBatchGames = $simCalculator->getMaxNrOfGamesSimultaneously($variantWithNrOfPlaces2, $selfRefereeInfo);
                $nrOfBatchesNeeded = (int)ceil($uniquePlacesCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param InfoToAssign $infoToAssign
     * @return bool
     */
    public function willMinNrOfBatchGamesBeReached(InfoToAssign $infoToAssign): bool
    {
        // $sportInfosWithMoreNrOfBatchesNeeded = $this->getSportInfosWithMoreNrOfBatchesNeeded($sportInfoMap);
        $simCalculator = new SimCalculator($this->input);
        $maxNrOfGamesSim = $simCalculator->getMaxNrOfGamesPerBatch($infoToAssign);
        if ($maxNrOfGamesSim < $this->planning->getMinNrOfBatchGames()) {
            return false;
        }

        $sortedSportInfos = $this->getSportInfosSortedByNrOfSimGames($infoToAssign);
        // $sortedSportInfos = $sportInfoMap->getSportInfoMap();

        $nrOfSimultaneousGames = 0;
        while ($nrOfSimultaneousGames < $this->planning->getMinNrOfBatchGames()) {
            $sportInfo = array_shift($sortedSportInfos);
            if ($sportInfo === null) {
                return false;
            }
            $nrOfSimultaneousSportGames = $simCalculator->getMaxNrOfSimultaneousSportGames($sportInfo);
            $nrOfSimultaneousGames += $nrOfSimultaneousSportGames;

            if ($sportInfo->getNrOfGames() >= $nrOfSimultaneousSportGames) {
                $minNrOfBatchesForSportNeeded = (int)floor($sportInfo->getNrOfGames() / $nrOfSimultaneousSportGames);
                // $maxNrOfGamesPerBatchLimit = (int)ceil($infoToAssign->getNrOfGames() / $minNrOfBatchesForSportNeeded);
                $maxNrOfGamesPerBatchLimit = $infoToAssign->getNrOfGames() / $minNrOfBatchesForSportNeeded;
                if ($maxNrOfGamesPerBatchLimit < $this->planning->getMinNrOfBatchGames()) {
                    return false;
                }
            }
        }
        if ($this->planning->getBatchGamesType() === BatchGamesType::RangeIsOneOrMore) {
            return $infoToAssign->getNrOfGames() >= $this->planning->getMinNrOfBatchGames();
        }

        $minNrOfBatchesForGamesPerPlaceNeeded = $this->getMinNrOfBatchesForGamesPerPlaceNeeded($infoToAssign);

        $restNrOfGames = $infoToAssign->getNrOfGames() % $this->planning->getMinNrOfBatchGames();
        $roundedNrOfGames = $infoToAssign->getNrOfGames() - $restNrOfGames;
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
     * @param InfoToAssign $infoToAssign
     * @return list<SportInfo>
     */
    public function getSportInfosSortedByNrOfSimGames(InfoToAssign $infoToAssign): array
    {
        $simCalculator = new SimCalculator($this->input);
        $sportInfos = $infoToAssign->getSportInfoMap();
        uasort($sportInfos, function (SportInfo $infoA, SportInfo $infoB) use ($simCalculator): int {
            $nrOfSimGamesA = $simCalculator->getMaxNrOfSimultaneousSportGames($infoA);
            $nrOfSimGamesB = $simCalculator->getMaxNrOfSimultaneousSportGames($infoB);
            return $nrOfSimGamesB - $nrOfSimGamesA;
        });
        return array_values($sportInfos);
    }

    protected function getMinNrOfBatchesForGamesPerPlaceNeeded(InfoToAssign $infoToAssign): int
    {
        $minNrOfBatchesNeeded = 0;
        foreach ($infoToAssign->getPlaceInfoMap() as $placeInfo) {
            if ($placeInfo->getNrOfGames() > $minNrOfBatchesNeeded) {
                $minNrOfBatchesNeeded = $placeInfo->getNrOfGames();
            }
        }
        return $minNrOfBatchesNeeded;
    }

    /**
     * @param int $batchNumber
     * @param InfoToAssign $infoToAssign
     * @return list<Place>
     */
    public function getRequiredPlaces(int $batchNumber, InfoToAssign $infoToAssign): array
    {
        $maxNrOfBatchesToGo = $this->planning->getMaxNrOfBatches() - $batchNumber;
        $requiredPlaces = [];
        foreach ($infoToAssign->getPlaceInfoMap() as $placeGameCounter) {
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
