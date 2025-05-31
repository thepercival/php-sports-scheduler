<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Exception;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\ScheduleOutput;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Input;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Game\GameAssigner;
use SportsScheduler\Game\PlannableGameCreator;
use SportsScheduler\Schedules\CycleCreator;

trait PlanningCreator
{
    use LoggerCreator;

//    protected function getAgainstH2hSportVariantWithFields(
//        int $nrOfFields,
//        int $nrOfHomePlaces = 1,
//        int $nrOfAwayPlaces = 1,
//        int $nrOfH2H = 1
//    ): SportVariantWithFields {
//        return new SportVariantWithFields(
//            $this->getAgainstH2hSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H),
//            $nrOfFields
//        );
//    }
//
//    protected function getAgainstGppSportVariantWithFields(
//        int $nrOfFields,
//        int $nrOfHomePlaces = 1,
//        int $nrOfAwayPlaces = 1,
//        int $nrOfGamesPerPlace = 1
//    ): SportVariantWithFields {
//        return new SportVariantWithFields(
//            $this->getAgainstGppSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace),
//            $nrOfFields
//        );
//    }
//
//    protected function getSingleSportVariantWithFields(
//        int $nrOfFields,
//        int $nrOfGamesPerPlace = 1,
//        int $nrOfGamePlaces = 1
//    ): SportVariantWithFields {
//        return new SportVariantWithFields(
//            $this->getSingleSportVariant($nrOfGamesPerPlace, $nrOfGamePlaces),
//            $nrOfFields
//        );
//    }
//
//    protected function getAllInOneGameSportVariantWithFields(
//        int $nrOfFields,
//        int $nrOfGamesPerPlace = 1
//    ): SportVariantWithFields {
//        return new SportVariantWithFields($this->getAllInOneGameSportVariant($nrOfGamesPerPlace), $nrOfFields);
//    }
//
//    protected function getDefaultNrOfReferees(): int
//    {
//        return 2;
//    }

    /**
     * @param list<int> $pouleStructureAsArray
     * @param list<SportWithNrOfFieldsAndNrOfCycles>|null $sportWithNrOfFieldsAndNrOfCycles
     * @param PlanningRefereeInfo|null $refereeInfo
     * @param bool $perPoule
     * @return PlanningConfiguration
     * @throws Exception
     */
    protected function createConfiguration(
        array $pouleStructureAsArray,
        array|null $sportWithNrOfFieldsAndNrOfCycles = null,
        PlanningRefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ): PlanningConfiguration
    {
        if ($sportWithNrOfFieldsAndNrOfCycles === null) {
            $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new PlanningRefereeInfo(2);
        }
        return new PlanningConfiguration(
            new PouleStructure(...$pouleStructureAsArray),
            $sportWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            $perPoule
        );
    }

    protected function createPlanning(
        PlanningConfiguration $configuration,
        SportRange $nrOfBatchGamesRange = null,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false,
        TimeoutState|null $timeoutState = null
    ): Planning {
        if ($nrOfBatchGamesRange === null) {
            $nrOfBatchGamesRange = new SportRange(1, 1);
        }
        $input = new Input($configuration);
        $planning = new Planning($input, $nrOfBatchGamesRange, $maxNrOfGamesInARow);
        if ($timeoutState !== null) {
            $planning->setTimeoutState($timeoutState);
        }

        $cycleCreator = new CycleCreator($this->createLogger());
        $sportRootCyclesMap = $cycleCreator->createSportCyclesMap($configuration);

//        foreach( $sportRootCyclesMap as $placeNr => $sportRootCycles) {
//            foreach( $sportRootCycles as $sportRootCycle) {
//                (new ScheduleOutput($this->createLogger()))->outputCycle($sportRootCycle);
//            }
//        }

        $gameCreator = new PlannableGameCreator($this->createLogger());
        $gameCreator->createGamesFromCycles($planning, $sportRootCyclesMap);

        $gameAssigner = new GameAssigner($this->createLogger());
        if ($disableThrowOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        if ($showHighestCompletedBatchNr) {
            $gameAssigner->showHighestCompletedBatchNr();
        }
        $betterNrOfBatchGames = $input->caluclateBetterNrOfBatchGames($planning->getType(), $nrOfBatchGamesRange);
        if( $gameAssigner->assignGames($planning, $betterNrOfBatchGames) !== PlanningState::Succeeded ) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }

    /**
     * @param Planning $planning
     * @return list<AgainstGame>
     */
    protected function getAgainstGames(Planning $planning): array {
        $games = [];
        foreach($planning->poules as $poule) {
            $games = array_merge($poule->getAgainstGames());
        }
        return $games;
    }

    /**
     * @param Planning $planning
     * @return list<TogetherGame>
     */
    protected function getTogetherGames(Planning $planning): array {
        $games = [];
        foreach($planning->poules as $poule) {
            $games = array_merge($poule->getTogetherGames());
        }
        return $games;
    }
}
