<?php

declare(strict_types=1);

namespace SportsScheduler\Game;


use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Game\TogetherGamePlace;
use SportsPlanning\Planning;
use SportsScheduler\Resource\Service\SportWithNrOfPlacesCreator;

class PreAssignSorter
{
    /**
     * @var array<int, array<int, int|float>>
     */
    private array $muliplierMap = [];

    /**
     * @param Planning $planning
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(Planning $planning): array
    {
        $sportMap = [];
        foreach( $planning->sports as $sportWithNrAndFields ) {
            $sportMap[$sportWithNrAndFields->sportNr] = $sportWithNrAndFields->sport;
        }

        $games = $planning->getGames();
        if( $planning->getConfiguration()->perPoule ) {
            uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2) use ($sportMap): int {
                $sport1 = $sportMap[$g1->getField()->sportNr];
                $g1Priority = $this->getPriority($g1, $sport1);
                $sport2 = $sportMap[$g2->getField()->sportNr];
                $g2Priority = $this->getPriority($g2, $sport2);
                if ($g1Priority !== $g2Priority) {
                    return $g1Priority - $g2Priority;
                }
                $pouleNr1 = $g1->poule->pouleNr;
                $pouleNr2 = $g2->poule->pouleNr;
                if ($pouleNr1 !== $pouleNr2) {
                    return $pouleNr1 - $pouleNr2;
                }
                return 0;
            });
            return array_values($games);
        }
        $this->initMultiplierMap($planning);

        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {
            $priority1 = $this->getWeightedPriority($g1);
            $priority2 = $this->getWeightedPriority($g2);
            if ($priority1 !== $priority2) {
                return $priority1 - $priority2;
            }
            $nrOfPoulePlaces1 = count($g1->poule->places);
            $nrOfPoulePlaces2 = count($g2->poule->places);
            if ($nrOfPoulePlaces1 !== $nrOfPoulePlaces2) {
                return $nrOfPoulePlaces2 - $nrOfPoulePlaces1;
            }
            $sumPlaceNrs1 = $this->getSumPlaceNrs($g1);
            $sumPlaceNrs2 = $this->getSumPlaceNrs($g2);
            if ($sumPlaceNrs1 !== $sumPlaceNrs2) {
                return $sumPlaceNrs1 - $sumPlaceNrs2;
            }
            return $g1->poule->pouleNr - $g2->poule->pouleNr;
        });
        return array_values($games);
    }

    protected function getPriority(
        AgainstGame|TogetherGame $game,
        TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sport): int
    {
        if ($game instanceof AgainstGame) {
            $nrOfPlaces = count($game->getPlaces());
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

    protected function getSumPlaceNrs(AgainstGame|TogetherGame $game): int
    {
        $total = 0;
        foreach ($game->getPlaces() as $gamePlace) {
            $total += $gamePlace->placeNr;
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
    protected function initMultiplierMap(Planning $planning): int
    {
        $maxNrOfPlaces = $planning->getConfiguration()->pouleStructure->getBiggestPoule();
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
        if (!isset($this->muliplierMap[$game->getField()->sportNr][$game->poule->pouleNr])) {
            return $priority;
        }
        $multiplier = $this->muliplierMap[$game->getField()->sportNr][$game->poule->pouleNr];
        return (int)($multiplier * $priority);
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
