<?php

declare(strict_types=1);

namespace SportsScheduler\Game;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Game\TogetherGamePlace;
use SportsPlanning\Input;
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
        $games = $planning->getGames();
        if( $planning->getInput()->getPerPoule() ) {
            uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {

                $g1Priority = $this->getPriority($g1);
                $g2Priority = $this->getPriority($g2);
                if ($g1Priority !== $g2Priority) {
                    return $g1Priority - $g2Priority;
                }
                $pouleNr1 = $g1->getPoule()->getNumber();
                $pouleNr2 = $g2->getPoule()->getNumber();
                if ($pouleNr1 !== $pouleNr2) {
                    return $pouleNr1 - $pouleNr2;
                }
                return 0;
            });
            return array_values($games);
        }
        $this->initMultiplierMap($planning->getInput());

        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {
            $priority1 = $this->getWeightedPriority($g1);
            $priority2 = $this->getWeightedPriority($g2);
            if ($priority1 !== $priority2) {
                return $priority1 - $priority2;
            }
            $nrOfPoulePlaces1 = $g1->getPoule()->getPlaces()->count();
            $nrOfPoulePlaces2 = $g2->getPoule()->getPlaces()->count();
            if ($nrOfPoulePlaces1 !== $nrOfPoulePlaces2) {
                return $nrOfPoulePlaces2 - $nrOfPoulePlaces1;
            }
            $sumPlaceNrs1 = $this->getSumPlaceNrs($g1);
            $sumPlaceNrs2 = $this->getSumPlaceNrs($g2);
            if ($sumPlaceNrs1 !== $sumPlaceNrs2) {
                return $sumPlaceNrs1 - $sumPlaceNrs2;
            }
            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
        });
        return array_values($games);
    }

    protected function getPriority(AgainstGame|TogetherGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->cyclePartNr;
        }
        $cycleNrs = array_map(function (TogetherGamePlace $gamePlace): int {
            return $gamePlace->cycleNr;
        }, $game->getPlaces()->toArray() );
        if( count($cycleNrs) === 0 ) {
            return 0;
        }
        return max($cycleNrs);
    }

    protected function getSumPlaceNrs(AgainstGame|TogetherGame $game): int
    {
        $total = 0;
        foreach ($game->getPlaces() as $gamePlace) {
            $total += $gamePlace->getPlace()->getPlaceNr();
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
    protected function initMultiplierMap(Input $input): int
    {
        $maxNrOfPlaces = $input->createPouleStructure()->getBiggestPoule();
        $this->muliplierMap = [];
        foreach ($input->getSports() as $plannableSport) {
            $sport = $plannableSport->sport;
            $sportWithLargestNrOfPlaces = (new SportWithNrOfPlacesCreator())->create($maxNrOfPlaces, $sport);
            $maxNrOfGamePlacesPerBatch = $sportWithLargestNrOfPlaces->calculateNrOfGamesPerPlace(1);
            $this->muliplierMap[$plannableSport->getNumber()] = [];
            foreach ($input->getPoules() as $poule) {
                $sportWithNrOfPlaces = (new SportWithNrOfPlacesCreator())->create(count($poule->getPlaces()), $sport);
                $nrOfGamePlacesPerBatch = $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1);
                // $nrOfGameRoundsPoule = $sportVariant->getNrOfGameRounds($poule->getPlaces()->count());
                $this->muliplierMap[$plannableSport->getNumber()][$poule->getNumber()] = $maxNrOfGamePlacesPerBatch / $nrOfGamePlacesPerBatch;
            }
        }
        return 1;
    }

    protected function getWeightedPriority(AgainstGame|TogetherGame $game): int
    {
        $priority = $this->getDefaultPriority($game);
        if (!isset($this->muliplierMap[$game->getSport()->getNumber()][$game->getPoule()->getNumber()])) {
            return $priority;
        }
        $multiplier = $this->muliplierMap[$game->getSport()->getNumber()][$game->getPoule()->getNumber()];
        return (int)($multiplier * $priority);
    }

    protected function getDefaultPriority(TogetherGame|AgainstGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->cyclePartNr;
        }
        $firstGamePlace = $game->getPlaces()->first();
        return $firstGamePlace !== false ? $firstGamePlace->cycleNr : 0;
    }
}
