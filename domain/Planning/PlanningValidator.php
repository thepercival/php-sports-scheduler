<?php

declare(strict_types=1);

namespace SportsScheduler\Planning;

use SportsHelpers\Against\AgainstSide;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning\PlanningValidity;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsScheduler\Exceptions\UnequalAssignedFieldsException;
use SportsScheduler\Exceptions\UnequalAssignedRefereePlacesException;
use SportsScheduler\Exceptions\UnequalAssignedRefereesException;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsScheduler\Planning\Validator\GameAssignments as GameAssignmentsValidator;
use SportsPlanning\Poule;

final class PlanningValidator
{
    public function __construct()
    {
    }

    public function validate(Planning $planning, bool $onlyUnassigned = false): int
    {
        $validity = $this->validateNrOfBatches($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateRefereesWithSelf($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateHasGamesAndAssignedGamePlaces($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateGamesInARow($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        if ($onlyUnassigned) {
            return $validity;
        }
        $validity = $this->validateRefereesCorrectlyAssigned($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateResourcesPerBatch($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateEquallyAssigned($planning);
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
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
            $invalidations[] = "the planning has not enough games";
        }
        if (($validity & PlanningValidity::UNEQUAL_GAME_HOME_AWAY) === PlanningValidity::UNEQUAL_GAME_HOME_AWAY) {
            $invalidations[] = "the planning has places that have an unequal number of home- or away-gameplaces";
        }
        if (($validity & PlanningValidity::UNEQUAL_GAME_AGAINST) === PlanningValidity::UNEQUAL_GAME_AGAINST) {
            $invalidations[] = "the planning has places that have an unequal number of against-gameplaces";
        }
        if (($validity & PlanningValidity::UNEQUAL_GAME_WITH) === PlanningValidity::UNEQUAL_GAME_WITH) {
            $invalidations[] = "the planning has places that have an unequal number of with-gameplaces";
        }
        if (($validity & PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES) === PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES) {
            $invalidations[] = "the planning has places with too much difference in nrOfHomeSides";
        }
        if (($validity & PlanningValidity::EMPTY_PLACE) === PlanningValidity::EMPTY_PLACE) {
            $invalidations[] = "the planning has a game with an empty place";
        }
        if (($validity & PlanningValidity::EMPTY_REFEREE) === PlanningValidity::EMPTY_REFEREE) {
            $invalidations[] = "the planning has a game with no referee";
        }
        if (($validity & PlanningValidity::EMPTY_REFEREEPLACE) === PlanningValidity::EMPTY_REFEREEPLACE) {
            $invalidations[] = "the planning has a game with no refereeplace";
        }
        if (($validity & PlanningValidity::NOT_EQUALLY_ASSIGNED_PLACES) === PlanningValidity::NOT_EQUALLY_ASSIGNED_PLACES) {
            $invalidations[] = "not all places within poule have same number of games";
        }
        if (($validity & PlanningValidity::TOO_MANY_GAMES_IN_A_ROW) === PlanningValidity::TOO_MANY_GAMES_IN_A_ROW) {
            $invalidations[] = "more than allowed number of games in a row";
        }
        if (($validity & PlanningValidity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH) === PlanningValidity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH) {
            $invalidations[] = "multiple assigned fields in batch";
        }
        if (($validity & PlanningValidity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH) === PlanningValidity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH) {
            $invalidations[] = "multiple assigned referees in batch";
        }
        if (($validity & PlanningValidity::MULTIPLE_ASSIGNED_PLACES_IN_BATCH) === PlanningValidity::MULTIPLE_ASSIGNED_PLACES_IN_BATCH) {
            $invalidations[] = "multiple assigned places in batch";
        }
        if (($validity & PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE) === PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE) {
            $invalidations[] = "refereeplace should (not) be referee in same poule";
        }
        if (($validity & PlanningValidity::INVALID_REFEREESELF_AND_REFEREES) === PlanningValidity::INVALID_REFEREESELF_AND_REFEREES) {
            $invalidations[] = "nrofreferees should we 0 when selfreferee is enabled";
        }
        if (($validity & PlanningValidity::INVALID_NROFBATCHES) === PlanningValidity::INVALID_NROFBATCHES) {
            $invalidations[] = "maxBatchNr of games is not equal to planning->getNrOfBatches";
        }
        if ($planning !== null) {
            if ((($validity & PlanningValidity::UNEQUALLY_ASSIGNED_FIELDS) === PlanningValidity::UNEQUALLY_ASSIGNED_FIELDS
                || ($validity & PlanningValidity::UNEQUALLY_ASSIGNED_REFEREES) === PlanningValidity::UNEQUALLY_ASSIGNED_REFEREES
                || ($validity & PlanningValidity::UNEQUALLY_ASSIGNED_REFEREEPLACES) === PlanningValidity::UNEQUALLY_ASSIGNED_REFEREEPLACES)
            ) {
                $invalidations[] = $this->getUnqualAssignedDescription($planning);
            }
        }
        if (count($invalidations) === 0) {
            throw new \Exception('an unknown invalid: ' . $validity, E_ERROR);
        }

        return $invalidations;
    }

    protected function validateRefereesWithSelf(Planning $planning): int
    {
        $refereeInfo = $planning->getConfiguration()->refereeInfo;
        if ($refereeInfo?->selfRefereeInfo !== null && count($planning->referees) > 0) {
            return PlanningValidity::INVALID_REFEREESELF_AND_REFEREES;
        }
        return PlanningValidity::VALID;
    }

    protected function validateHasGamesAndAssignedGamePlaces(Planning $planning): int
    {
        foreach ($planning->poules as $poule) {
            $pouleGames = $poule->getGames();
            if (count($pouleGames) === 0) {
                return PlanningValidity::NO_GAMES;
            }
            $validity = $this->validateAllGamePlacesAssigned($planning, $poule);
            if ($validity !== PlanningValidity::VALID) {
                return $validity;
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateNrOfBatches(Planning $planning): int
    {
        $games = $planning->getGames();
        if (count($games) === 0) {
            return 0 === $planning->getNrOfBatches() ? PlanningValidity::VALID : PlanningValidity::INVALID_NROFBATCHES;
        }
        $maxBatchNr = max(
            array_map(function (AgainstGame|TogetherGame $game): int {
                return $game->getBatchNr();
            }, $games)
        );
        return $maxBatchNr === $planning->getNrOfBatches() ? PlanningValidity::VALID : PlanningValidity::INVALID_NROFBATCHES;
    }

    protected function validateAllGamePlacesAssigned(Planning $planning, Poule $poule): int
    {
        foreach ($planning->sports as $sportWithNrAndFields) {
            $invalid = $this->validateAllGamePlacesAssignedForSport($planning, $poule, $sportWithNrAndFields);
            if ($invalid !== PlanningValidity::VALID) {
                return $invalid;
            }
        }
        return PlanningValidity::VALID;
    }


    protected function validateAllGamePlacesAssignedForSport(
        Planning $planning, Poule $poule,
        TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields $sportWithNrAndFields): int
    {
        $nrOfGamesPerPlace = [];
        $nrOfPlaces = count($poule->places);
        /** @var non-empty-array<int, int> $nrOfHomeSideGames */
        $nrOfHomeSideGames = [];

        if (!($sportWithNrAndFields->sport instanceof TogetherSport)) {

            foreach ($poule->places as $place) {
                $nrOfHomeSideGames[$place->getUniqueIndex()] = 0;
            }
        }

        $games = array_filter($poule->getGames(),
            function (AgainstGame|TogetherGame $game) use ($sportWithNrAndFields): bool {
                return $game->getField()->sportNr === $sportWithNrAndFields->sportNr;
            });
        foreach ($games as $game) {
            $gameSportWithNrAndFields = $planning->getSport($game->getField()->sportNr);
            if( !($gameSportWithNrAndFields->sport instanceof TogetherSport))
            {
                if( !($game instanceof AgainstGame )) {
                    return PlanningValidity::EMPTY_PLACE;
                }
                $homeGamePlaces = $game->getSideGamePlaces(AgainstSide::Home);
                $awayGamePlaces = $game->getSideGamePlaces(AgainstSide::Away);
                if (count($homeGamePlaces) === 0 || count($awayGamePlaces) === 0) {
                    return PlanningValidity::EMPTY_PLACE;
                }
            }
            if (count($game->getPlaces()) === 0) {
                return PlanningValidity::EMPTY_PLACE;
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateRefereesCorrectlyAssigned(Planning $planning): int
    {
        foreach ($planning->poules as $poule) {
            $validity = $this->validateRefereesCorrectlyAssignedHelper($planning, $poule);
            if ($validity !== PlanningValidity::VALID) {
                return $validity;
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateRefereesCorrectlyAssignedHelper(Planning $planning, Poule $poule): int
    {
        $refereeInfo = $planning->getConfiguration()->refereeInfo;
        if( $refereeInfo === null) {
            return PlanningValidity::VALID;
        }
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        foreach ($poule->getGames() as $game) {
            if ($selfRefereeInfo !== null) {
                $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
                if ($refereePlaceUniqueIndex === null) {
                    return PlanningValidity::EMPTY_REFEREEPLACE;
                }
                $refereePlace = $planning->getPlace($refereePlaceUniqueIndex);
                $refereePoule = $planning->getPoule($refereePlace->pouleNr);
                if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule
                    && $refereePoule !== $game->poule) {
                    return PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE;
                }
                if ($selfRefereeInfo->selfReferee === SelfReferee::OtherPoules
                    && $refereePoule === $game->poule) {
                    return PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE;
                }
            } else {
                if (count($planning->referees) > 0) {
                    if ($game->getRefereeNr() === null) {
                        return PlanningValidity::EMPTY_REFEREE;
                    }
                }
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateGamesInARow(Planning $planning): int
    {
        if ($planning->maxNrOfGamesInARow === 0) {
            return PlanningValidity::VALID;
        }
        foreach ($planning->poules as $poule) {
            foreach ($poule->places as $place) {
                if ($this->checkGamesInARowForPlace($planning, $place) === false) {
                    return PlanningValidity::TOO_MANY_GAMES_IN_A_ROW;
                }
            }
        }
        return PlanningValidity::VALID;
    }

    protected function checkGamesInARowForPlace(Planning $planning, Place $place): bool
    {
        /**
         * @param Place $place
         * @return array<int,bool>
         */
        $getBatchParticipations = function (Place $place) use ($planning): array {
            $games = $planning->getGames(Planning::ORDER_GAMES_BY_BATCH);
            $batchMap = [];
            foreach ($games as $game) {
                if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                    $batchMap[$game->getBatchNr()] = false;
                }
                if ($batchMap[$game->getBatchNr()] === true) {
                    continue;
                }
                $batchMap[$game->getBatchNr()] = $game->isParticipating($place->placeNr);
            }
            return $batchMap;
        };
        /**
         * @param array<int,bool> $batchParticipations
         * @return int
         */
        $getMaxInARow = function (array $batchParticipations): int {
            $maxNrOfGamesInRow = 0;
            $currentMaxNrOfGamesInRow = 0;
            /** @var bool $batchParticipation */
            foreach ($batchParticipations as $batchParticipation) {
                if ($batchParticipation) {
                    $currentMaxNrOfGamesInRow++;
                    if ($currentMaxNrOfGamesInRow > $maxNrOfGamesInRow) {
                        $maxNrOfGamesInRow = $currentMaxNrOfGamesInRow;
                    }
                } else {
                    $currentMaxNrOfGamesInRow = 0;
                }
            }
            return $maxNrOfGamesInRow;
        };

        return $getMaxInARow($getBatchParticipations($place)) <= $planning->maxNrOfGamesInARow;
    }

//    /**
//     * @param Game $game
//     * @param int|null $side
//     * @return array|Place[]
//     */
//    protected function getPlaces(Game $game, int $side = null): array
//    {
//        return $game->getPlaces($side)->map(
//            function (GamePlace $gamePlace): Place {
//                return $gamePlace->getPlace();
//            }
//        )->toArray();
//    }

    protected function validateResourcesPerBatch(Planning $planning): int
    {
        $games = $planning->getGames(Planning::ORDER_GAMES_BY_BATCH);
        $batchMap = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                $batchMap[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $places = $game->getPlaces();
            $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
            if ($refereePlaceUniqueIndex !== null) {
                $places[] = $planning->getPlace($refereePlaceUniqueIndex);
            }
            foreach ($places as $placeIt) {
                /** @var bool|int|string $search */
                $search = array_search($placeIt, $batchMap[$game->getBatchNr()]["places"], true);
                if ($search !== false) {
                    return PlanningValidity::MULTIPLE_ASSIGNED_PLACES_IN_BATCH;
                }
                array_push($batchMap[$game->getBatchNr()]["places"], $placeIt);
            }

            $search = array_search($game->getField(), $batchMap[$game->getBatchNr()]["fields"], true);
            /** @var bool|int|string $search */
            if ($search !== false) {
                return PlanningValidity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH;
            }
            array_push($batchMap[$game->getBatchNr()]["fields"], $game->getField());

            $refereeNr = $game->getRefereeNr();
            if ($refereeNr !== null) {
                $referee = $planning->getReferee($refereeNr);
                /** @var bool|int|string $search */
                $search = array_search($referee, $batchMap[$game->getBatchNr()]["referees"], true);
                if ($search !== false) {
                    return PlanningValidity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH;
                }
                array_push($batchMap[$game->getBatchNr()]["referees"], $referee);
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateEquallyAssigned(Planning $planning): int
    {
        try {
            $assignmentValidator = new GameAssignmentsValidator($planning);
            $assignmentValidator->validate();
        } catch (UnequalAssignedFieldsException $e) {
            return PlanningValidity::UNEQUALLY_ASSIGNED_FIELDS;
        } catch (UnequalAssignedRefereesException $e) {
            return PlanningValidity::UNEQUALLY_ASSIGNED_REFEREES;
        } catch (UnequalAssignedRefereePlacesException $e) {
            return PlanningValidity::UNEQUALLY_ASSIGNED_REFEREEPLACES;
        }
        return PlanningValidity::VALID;
    }

    protected function getUnqualAssignedDescription(Planning $planning): string
    {
        try {
            $assignmentValidator = new GameAssignmentsValidator($planning);
            $assignmentValidator->validate();
        } catch (UnequalAssignedFieldsException | UnequalAssignedRefereesException | UnequalAssignedRefereePlacesException $e) {
            return $e->getMessage();
        }/* catch( Exception $e ) {
            return 'unknown exception: ' . $e->getMessage();
        }*/
        return 'no exception';
    }
}
