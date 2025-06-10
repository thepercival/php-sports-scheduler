<?php

declare(strict_types=1);

namespace SportsScheduler\Game;


use Doctrine\ORM\Configuration;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Game\TogetherGamePlace;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsScheduler\Resource\Service\SportWithNrOfPlacesCreator;

final class PreAssignSorter
{
    /**
     * @var array<int, array<int, int|float>>
     */
    private array $muliplierMap = [];

    /**
     * @param Planning $planning
     * @param PlanningConfiguration $configuration
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(Planning $planning, PlanningConfiguration $configuration): array
    {
        $sportMap = [];
        foreach( $planning->sports as $sportWithNrAndFields ) {
            $sportMap[$sportWithNrAndFields->sportNr] = $sportWithNrAndFields->sport;
        }

        $games = $planning->getGames();
        if( $configuration->perPoule ) {
            uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2) use ($planning, $sportMap): int {
                $sport1 = $sportMap[$g1->getField()->sportNr];
                $g1Priority = $this->getPriority($planning, $g1, $sport1);
                $sport2 = $sportMap[$g2->getField()->sportNr];
                $g2Priority = $this->getPriority($planning, $g2, $sport2);
                if ($g1Priority !== $g2Priority) {
                    return $g1Priority - $g2Priority;
                }
                if ($g1->pouleNr !== $g2->pouleNr) {
                    return $g1->pouleNr - $g2->pouleNr;
                }
                return 0;
            });
            return array_values($games);
        }
        $this->initMultiplierMap($planning, $configuration);

        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2) use($planning): int {
            $priority1 = $this->getWeightedPriority($g1);
            $priority2 = $this->getWeightedPriority($g2);
            if ($priority1 !== $priority2) {
                return $priority1 - $priority2;
            }
            $nrOfPoulePlaces1 = count($planning->getPoule($g1->pouleNr)->places);
            $nrOfPoulePlaces2 = count($planning->getPoule($g2->pouleNr)->places);
            if ($nrOfPoulePlaces1 !== $nrOfPoulePlaces2) {
                return $nrOfPoulePlaces2 - $nrOfPoulePlaces1;
            }
            $sumPlaceNrs1 = $this->getSumPlaceNrs($planning, $g1);
            $sumPlaceNrs2 = $this->getSumPlaceNrs($planning, $g2);
            if ($sumPlaceNrs1 !== $sumPlaceNrs2) {
                return $sumPlaceNrs1 - $sumPlaceNrs2;
            }
            return $g1->pouleNr - $g2->pouleNr;
        });
        return array_values($games);
    }

    protected function getPriority(
        Planning $planning,
        AgainstGame|TogetherGame $game,
        TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sport): int
    {
        if ($game instanceof AgainstGame) {
            $nrOfPlaces = count($planning->getPoule($game->pouleNr)->places);
            $sportWithNrOfPlaces = (new SportWithNrOfPlacesCreator())->create($nrOfPlaces, $sport);
            $nrOfGamesPerPlaceForSingleCycle = $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1);
            return $game->cyclePartNr + ( ($game->cycleNr - 1) * $nrOfGamesPerPlaceForSingleCycle );
        }
        $cycleNrs = array_map(function (TogetherGamePlace $gamePlace): int {
            return $gamePlace->cycleNr;
        }, $game->getGamePlaces() );
        if( count($cycleNrs) === 0 ) {
            return 0;
        }
        return max($cycleNrs);
    }

    protected function getSumPlaceNrs(Planning $planning, AgainstGame|TogetherGame $game): int
    {
        $total = 0;
        $poule = $planning->getPoule($game->pouleNr);
        foreach ($poule->getPlaces($game) as $place) {
            $total += $place->placeNr;
        }
        return $total;
    }

//        1 1.1 vs 1.2    1 2.1 vs 2.2
//        1 1.3 vs 1.4    1 2.3 vs 2.4
//        2 1.5 vs 1.1    2 2.4 vs 2.1
//        2 1.2 vs 1.3    2 2.2 vs 2.3
//        3 1.4 vs 1.5    3 2.1 vs 2.3
//        3 1.3 vs 1.1    3 2.2 vs 2.4
//        4 1.2 vs 1.4
//        4 1.5 vs 1.3
//        5 1.1 vs 1.4
//        5 1.2 vs 1.5
//
//        1 1.1 vs 1.2    1 * 5/3 = 1.66     2.1 vs 2.2
//        1 1.3 vs 1.4    1 * 5/3 = 1.66     2.3 vs 2.4
//        2 1.5 vs 1.1    2 * 5/3 = 3.33     2.4 vs 2.1
//        2 1.2 vs 1.3    2 * 5/3 = 3.33     2.2 vs 2.3
//        3 1.4 vs 1.5    3 * 5/3 = 5        2.1 vs 2.3
//        3 1.3 vs 1.1    3 * 5/3 = 5        2.2 vs 2.4
//        4 1.2 vs 1.4
//        4 1.5 vs 1.3
//        5 1.1 vs 1.4
//        5 1.2 vs 1.5
    protected function initMultiplierMap(Planning $planning, PlanningConfiguration $configuration): int
    {
        $maxNrOfPlaces = $configuration->pouleStructure->getBiggestPoule();
        $this->muliplierMap = [];
        foreach ($planning->sports as $sportWithNrAndFields) {
            $sportNr = $sportWithNrAndFields->sportNr;
            $sport = $sportWithNrAndFields->sport;
            $sportWithLargestNrOfPlaces = (new SportWithNrOfPlacesCreator())->create($maxNrOfPlaces, $sport);
            $maxNrOfGamePlacesPerBatch = $sportWithLargestNrOfPlaces->calculateNrOfGamesPerPlace(1);
            $this->muliplierMap[$sportNr] = [];
            foreach ($planning->poules as $poule) {
                $sportWithNrOfPlaces = (new SportWithNrOfPlacesCreator())->create(count($poule->places), $sport);
                $nrOfGamePlacesPerBatch = $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1);
                // $nrOfGameRoundsPoule = $sportVariant->getNrOfGameRounds($poule->getPlaces()->count());
                $this->muliplierMap[$sportNr][$poule->pouleNr] = $maxNrOfGamePlacesPerBatch / $nrOfGamePlacesPerBatch;
            }
        }
        return 1;
    }

    protected function getWeightedPriority(AgainstGame|TogetherGame $game): int
    {
        $priority = $this->getDefaultPriority($game);
        if (!isset($this->muliplierMap[$game->getField()->sportNr][$game->pouleNr])) {
            return $priority;
        }
        $multiplier = $this->muliplierMap[$game->getField()->sportNr][$game->pouleNr];
        return (int)((int)$multiplier * $priority);
    }

    protected function getDefaultPriority(TogetherGame|AgainstGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->cyclePartNr;
        }
        $gamePlaces = $game->getGamePlaces();
        $firstGamePlace = reset($gamePlaces);
        return $firstGamePlace !== false ? $firstGamePlace->cycleNr : 0;
    }
}
