<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule;

use SportsHelpers\Against\AgainstSide;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\AllInOneGame as AllInOneGameWithPoule;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Game;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validity as PlanningValidity;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Sport;
use SportsScheduler\Combinations\Validators\AgainstValidator as AgainstValidator;
use SportsScheduler\Combinations\Validators\WithValidator as WithValidator;
use SportsScheduler\Exceptions\UnequalAssignedFieldsException;
use SportsScheduler\Exceptions\UnequalAssignedRefereePlacesException;
use SportsScheduler\Exceptions\UnequalAssignedRefereesException;
use SportsScheduler\Planning\PlanningGameAssignmentsValidator as GameAssignmentsValidator;

final class ScheduleValidator
{
    public function __construct()
    {

    }

    public function validate(Schedule $schedule): int
    {
        $validity = $this->validateSportHasGames($schedule);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }

        $validity = $this->validateNrOfGames($schedule);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }

        $validity = $this->validateNrOfHomeGames($schedule);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        return PlanningValidity::VALID;
    }

    private function validateSportHasGames(Schedule $schedule): int
    {
        $assignCounter = new AssignedCounter(
            $schedule->getNrOfPlaces(),
            $schedule->createSportVariants()
        );

        foreach( $schedule->getScheduleSports() as $scheduleSport ) {
            if (count($scheduleSport->getGames()) === 0) {
                return PlanningValidity::NO_GAMES;
            }
        }
        return PlanningValidity::VALID;
    }

    private function validateNrOfGames(Schedule $schedule): int
    {
        $assignCounter = new AssignedCounter(
            $schedule->getNrOfPlaces(),
            $schedule->createSportVariants()
        );

        $assignCounter->assignHomeAways($schedule->createHomeAwaysForAgainstSports());
        if( $assignCounter->getAmountDifference() > 0 ) {
            return PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES;
        }
        return PlanningValidity::VALID;
    }

    private function validateNrOfHomeGames(Schedule $schedule): int {
        $assignCounter = new AssignedCounter(
            $schedule->getNrOfPlaces(),
            $schedule->createSportVariants()
        );

        $assignCounter->assignHomeAways($schedule->createHomeAwaysForAgainstSports());
        if( $assignCounter->getHomeAmountDifference() > 0 ) {
            return PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES;
        }
        return PlanningValidity::VALID;

    }

    /**
     * @param int $validity
     * @param Planning|null $planning
     * @return list<string>
     */
    public function getValidityDescriptions(int $validity, Planning|null $planning = null): array
    {
        $invalidations = [];
        if ($validity === 0) {
            return $invalidations;
        }
        if (($validity & PlanningValidity::NO_GAMES) === PlanningValidity::NO_GAMES) {
            $invalidations[] = "a scheduleSport has no games";
        }
        if (($validity & PlanningValidity::UNEQUAL_GAME_AGAINST) === PlanningValidity::UNEQUAL_GAME_AGAINST) {
            $invalidations[] = "the schedule has places with difference in nrofgames";
        }
        if (($validity & PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES) === PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES) {
            $invalidations[] = "the schedule has places with too much difference in nrOfHomeSides";
        }

        if (count($invalidations) === 0) {
            throw new \Exception('an unknown invalid: ' . $validity, E_ERROR);
        }

        return $invalidations;
    }



}
