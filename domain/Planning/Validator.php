<?php

declare(strict_types=1);

namespace SportsScheduler\Planning;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsPlanning\Planning\Validity as PlanningValidity;
use SportsScheduler\Combinations\Validators\AgainstValidator;
use SportsScheduler\Combinations\Validators\WithValidator;
use SportsScheduler\Exceptions\UnequalAssignedFieldsException;
use SportsScheduler\Exceptions\UnequalAssignedRefereePlacesException;
use SportsScheduler\Exceptions\UnequalAssignedRefereesException;
use SportsPlanning\Game;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsScheduler\Planning\Validator\GameAssignments as GameAssignmentsValidator;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Single as SingleWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\AllInOneGame as AllInOneGameWithNrOfPlaces;

class Validator
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
        $validity = $this->validateRefereesWithSelf($planning->getInput());
        if (PlanningValidity::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateGamesAndGamePlaces($planning);
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
        $validity = $this->validateResourcesCorrectlyAssigned($planning);
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

    protected function validateRefereesWithSelf(Input $input): int
    {
        if ($input->selfRefereeEnabled() && $input->getReferees()->count() > 0) {
            return PlanningValidity::INVALID_REFEREESELF_AND_REFEREES;
        }
        return PlanningValidity::VALID;
    }

    protected function validateGamesAndGamePlaces(Planning $planning): int
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            $pouleGames = $planning->getGamesForPoule($poule);
            if (count($pouleGames) === 0) {
                return PlanningValidity::NO_GAMES;
            }
            $validity = $this->validateNrOfBatches($planning);
            if (PlanningValidity::VALID !== $validity) {
                return $validity;
            }

            $validity = $this->allPlacesInPouleSameNrOfGames($planning, $poule);
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

    protected function allPlacesInPouleSameNrOfGames(Planning $planning, Poule $poule): int
    {
        foreach ($planning->getInput()->getSports() as $sport) {
            $invalid = $this->allPlacesInPouleSameNrOfSportGames($planning, $poule, $sport);
            if ($invalid !== PlanningValidity::VALID) {
                return $invalid;
            }
        }
        return PlanningValidity::VALID;
    }

    protected function allPlacesInPouleSameNrOfSportGames(Planning $planning, Poule $poule, Sport $sport): int
    {
        $nrOfGamesPerPlace = [];
        $nrOfPlaces = count($poule->getPlaces());
        /** @var non-empty-array<int, int> $nrOfHomeSideGames */
        $nrOfHomeSideGames = [];
        $sportVariant = $sport->createVariant();

        if ($sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
            if( $sportVariant instanceof AgainstH2h ) {
                $againstWithNrOfPlaces = new AgainstH2hWithNrOfPlaces($nrOfPlaces, $sportVariant);
            } else {
                $againstWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
            }
            foreach ($poule->getPlaces() as $place) {
                $nrOfHomeSideGames[$place->getUniqueIndex()] = 0;
            }
            // if ($againstWithPoule instanceof AgainstH2hWithPoule || $againstWithPoule->allPlacesSameNrOfGamesAssignable()) {
                if (// $sportVariant->hasMultipleSidePlaces() &&
                    ($againstWithNrOfPlaces instanceof AgainstH2hWithNrOfPlaces || $againstWithNrOfPlaces->allWithSameNrOfGamesAssignable())) {
                    $withValidator = new WithValidator();
                    $withValidator->addGames($planning, $poule, $sport);
                    if (!$withValidator->balanced()) {
                        return PlanningValidity::UNEQUAL_GAME_WITH;
                    }
                }
                if ($againstWithNrOfPlaces instanceof AgainstH2hWithNrOfPlaces || $againstWithNrOfPlaces->allAgainstSameNrOfGamesAssignable()) {
                    $againstValidator = new AgainstValidator();
                    $againstValidator->addGames($planning, $poule, $sport);
                    if (!$againstValidator->balanced()) {
                        return PlanningValidity::UNEQUAL_GAME_AGAINST;
                    }
                }
            // }
        }

        $sportGames = array_filter($planning->getGamesForPoule($poule), function (Game $game) use ($sport): bool {
            return $game->getSport() === $sport;
        });
        foreach ($sportGames as $game) {
            $sportVariant = $game->createVariant();
            if ($sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                if (!$game instanceof AgainstGame) {
                    return PlanningValidity::UNEQUAL_GAME_HOME_AWAY;
                }
                $homePlaces = $game->getSidePlaces(AgainstSide::Home);
                $awayPlaces = $game->getSidePlaces(AgainstSide::Away);
                $nrOfHomePlaces = count($homePlaces);
                $nrOfAwayPlaces = count($awayPlaces);
                if ($nrOfHomePlaces === 0 || $nrOfAwayPlaces === 0) {
                    return PlanningValidity::EMPTY_PLACE;
                }
                if ($sportVariant->getNrOfHomePlaces() === $sportVariant->getNrOfAwayPlaces()) {
                    if ($sportVariant->getNrOfHomePlaces() !== $nrOfHomePlaces
                        || $sportVariant->getNrOfAwayPlaces() !== $nrOfAwayPlaces) {
                        return PlanningValidity::UNEQUAL_GAME_HOME_AWAY;
                    }
                } else {
                    if (
                        ($sportVariant->getNrOfHomePlaces() !== $nrOfHomePlaces && $sportVariant->getNrOfAwayPlaces(
                            ) !== $nrOfHomePlaces)
                        ||
                        ($sportVariant->getNrOfHomePlaces() !== $nrOfAwayPlaces && $sportVariant->getNrOfAwayPlaces(
                            ) !== $nrOfAwayPlaces)) {
                        return PlanningValidity::UNEQUAL_GAME_HOME_AWAY;
                    }
                }

                foreach ($homePlaces as $homePlace) {
                    $nrOfHomeSideGames[$homePlace->getPlace()->getUniqueIndex()]++;
                }
            } elseif ($sportVariant instanceof AllInOneGame) {
                if ($poule->getPlaces()->count() !== $game->getPlaces()->count()) {
                    return PlanningValidity::UNEQUAL_GAME_HOME_AWAY;
                }
            }
            if ($game->getPlaces()->count() === 0) {
                return PlanningValidity::EMPTY_PLACE;
            }
            $places = $game->getPoulePlaces();
            foreach ($places as $place) {
                if (array_key_exists((string)$place, $nrOfGamesPerPlace) === false) {
                    $nrOfGamesPerPlace[(string)$place] = 0;
                }
                $nrOfGamesPerPlace[(string)$place]++;
            }
        }

        $variantWithNrOfPlaces = (new VariantCreator())->createWithNrOfPlaces($nrOfPlaces, $sportVariant);
        if (!($variantWithNrOfPlaces instanceof AgainstGppWithNrOfPlaces) || $variantWithNrOfPlaces->allPlacesSameNrOfGamesAssignable()) {
            $nrOfGamesFirstPlace = reset($nrOfGamesPerPlace);
            foreach ($nrOfGamesPerPlace as $nrOfGamesSomePlace) {
                if ($nrOfGamesFirstPlace !== $nrOfGamesSomePlace) {
                    return PlanningValidity::NOT_EQUALLY_ASSIGNED_PLACES;
                }
            }
        }

        if ($variantWithNrOfPlaces instanceof SingleWithNrOfPlaces || $variantWithNrOfPlaces instanceof AllInOneGameWithNrOfPlaces) {
            return PlanningValidity::VALID;
        }
        if ($variantWithNrOfPlaces instanceof AgainstGppWithNrOfPlaces) {
            if (!$variantWithNrOfPlaces->allWithSameNrOfGamesAssignable(AgainstSide::Home)) {
                return PlanningValidity::VALID;
            }
        }
        $maxDifference = 1;

//        $totalNrOfHomePlaces = $variantWithPoule->getTotalNrOfGames() * $variantWithPoule->getSportVariant()->getNrOfHomePlaces();
//        if (($totalNrOfHomePlaces % $nrOfPlaces ) > 0) {
//            $maxDifference++;
//        }
        $minValue = min($nrOfHomeSideGames);
        foreach ($nrOfHomeSideGames as $amount) {
            if ($amount - $minValue > $maxDifference) {
                return PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES;
            }
        }

        return PlanningValidity::VALID;
    }

    protected function validateResourcesCorrectlyAssigned(Planning $planning): int
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            $validity = $this->validateResourcesCorrectlyAssignedHelper($planning, $poule);
            if ($validity !== PlanningValidity::VALID) {
                return $validity;
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateResourcesCorrectlyAssignedHelper(Planning $planning, Poule $poule): int
    {
        foreach ($planning->getGamesForPoule($poule) as $game) {
            if ($planning->getInput()->selfRefereeEnabled()) {
                $refereePlace = $game->getRefereePlace();
                if ($refereePlace === null) {
                    return PlanningValidity::EMPTY_REFEREEPLACE;
                }
                if ($planning->getInput()->getSelfReferee() === SelfReferee::SamePoule
                    && $refereePlace->getPoule() !== $game->getPoule()) {
                    return PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE;
                }
                if ($planning->getInput()->getSelfReferee() === SelfReferee::OtherPoules
                    && $refereePlace->getPoule() === $game->getPoule()) {
                    return PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE;
                }
            } else {
                if ($planning->getInput()->getReferees()->count() > 0) {
                    if ($game->getReferee() === null) {
                        return PlanningValidity::EMPTY_REFEREE;
                    }
                }
            }
        }
        return PlanningValidity::VALID;
    }

    protected function validateGamesInARow(Planning $planning): int
    {
        if ($planning->getMaxNrOfGamesInARow() === 0) {
            return PlanningValidity::VALID;
        }
        foreach ($planning->getInput()->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
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
            $games = $planning->getGames(Game::ORDER_BY_BATCH);
            $batchMap = [];
            foreach ($games as $game) {
                if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                    $batchMap[$game->getBatchNr()] = false;
                }
                if ($batchMap[$game->getBatchNr()] === true) {
                    continue;
                }
                $batchMap[$game->getBatchNr()] = $game->isParticipating($place);
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

        return $getMaxInARow($getBatchParticipations($place)) <= $planning->getMaxNrOfGamesInARow();
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
        $games = $planning->getGames(Game::ORDER_BY_BATCH);
        $batchMap = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                $batchMap[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $places = $game->getPoulePlaces();
            $refereePlace = $game->getRefereePlace();
            if ($refereePlace !== null) {
                $places[] = $refereePlace;
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

            $referee = $game->getReferee();
            if ($referee !== null) {
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
