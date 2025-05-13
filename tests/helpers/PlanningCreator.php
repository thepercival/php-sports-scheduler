<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Exception;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfFields;
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
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array|null $sportWithNrOfFieldsAndNrOfCycles = null,
        PlanningRefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportWithNrOfFieldsAndNrOfCycles === null) {
            $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new PlanningRefereeInfo(2);
        }
        $input = new Input( new Input\Configuration(
            new PouleStructure(...$pouleStructureAsArray),
            $sportWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            $perPoule
        ) );

        return $input;
    }

    /**
     * @param Configuration $config
     * @return array<int, list<ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo>>
     */
    protected function createSportCyclesMap(Configuration $config): array {
        /** @var array<int, list<ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo>> $sportCyclesMap */
        $sportCyclesMap = [];
        {
            $cycleCreator = new CycleCreator($this->createLogger());
            $pouleStructure = $config->pouleStructure;
            for( $nrOfPlaces = $pouleStructure->getSmallestPoule() ; $nrOfPlaces <= $pouleStructure->getBiggestPoule() ; $nrOfPlaces++) {
                $sportRootCycles = $cycleCreator->createSportRootCycles(
                    new ScheduleWithNrOfPlaces( $nrOfPlaces, $config->createSportsWithNrOfCycles())
                );
                $sportCyclesMap[$nrOfPlaces] = $sportRootCycles;
            }
        }
        return $sportCyclesMap;
    }


    protected function createPlanning(
        Configuration $configuration,
        SportRange $nrOfBatchGamesRange = null,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false,
        TimeoutState|null $timeoutState = null
    ): Planning {
        if ($nrOfBatchGamesRange === null) {
            $nrOfBatchGamesRange = new SportRange(1, 1);
        }
        $planning = new Planning(new Input($configuration), $nrOfBatchGamesRange, $maxNrOfGamesInARow);
        if ($timeoutState !== null) {
            $planning->setTimeoutState($timeoutState);
        }

        $sportCyclesMap = $this->createSportCyclesMap($configuration);

//        $rootCycles = $cycleCreator->createCycles($scheduleWithNrOfPlaces);
//        $schedules = $scheduleCreator->createFromInput($input);

        $gameCreator = new PlannableGameCreator($this->createLogger());
        $gameCreator->createGamesFromCycles($planning, $sportCyclesMap);

        $gameAssigner = new GameAssigner($this->createLogger());
        if ($disableThrowOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        if ($showHighestCompletedBatchNr) {
            $gameAssigner->showHighestCompletedBatchNr();
        }
        if( $gameAssigner->assignGames($planning) !== PlanningState::Succeeded ) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
