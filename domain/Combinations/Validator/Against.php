<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validator;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsScheduler\Combinations\Validator;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Against extends Validator
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);
    }

    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }

        foreach( $game->getSidePlaces(Side::Home) as $homeGamePlace ) {

            $placeCounterMap = $this->placeCounterMaps[$homeGamePlace->getPlace()->getPlaceNr()];
//            if ($placeCounterMap === null ) {
//                throw new \Exception('placeCounter not found');
//            }
            foreach( $game->getSidePlaces(Side::Away) as $awayGamePlace ) {
                $placeCounterMap = $placeCounterMap->addPlace($awayGamePlace->getPlace());
            }
            $this->placeCounterMaps[$homeGamePlace->getPlace()->getPlaceNr()] = $placeCounterMap;
        }

        foreach( $game->getSidePlaces(Side::Away) as $awayGamePlace ) {

            $placeCounterMap = $this->placeCounterMaps[$awayGamePlace->getPlace()->getPlaceNr()];
//            if ($placeCounterMap === null ) {
//                throw new \Exception('placeCounter not found');
//            }
            foreach( $game->getSidePlaces(Side::Home) as $homeGamePlace ) {
                $placeCounterMap = $placeCounterMap->addPlace($homeGamePlace->getPlace());
            }
            $this->placeCounterMaps[$awayGamePlace->getPlace()->getPlaceNr()] = $placeCounterMap;
        }

//        $homeAway = new HomeAway(
//            new PlaceCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Home)->toArray() )
//            ) ),
//            new PlaceCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Away)->toArray() )
//            ) )
//        );
//        // WHEN CHECKING AGAINST, JUST CHECK 1 VS 1 EVEN IF 2 VS 2 ganes
//        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
//            $againstPlaceCombination->
//
//        }
//        $homePlaceCombination = $this->getPlaceCombination($game, Side::Home);
//        $awayPlaceCombination = $this->getPlaceCombination($game, Side::Away);
//        if (isset($this->counters[$homePlaceCombination->getIndex()])) {
//            $this->counters[$homePlaceCombination->getIndex()]->addCombination($awayPlaceCombination);
//        }
//        if (isset($this->counters[$awayPlaceCombination->getIndex()])) {
//            $this->counters[$awayPlaceCombination->getIndex()]->addCombination($homePlaceCombination);
//        }
    }

//    public function balanced(): bool
//    {
//        foreach ($this->counters as $counter) {
//            if (!$counter->balanced()) {
//                return false;
//            }
//        }
//        return true;
//    }

//    public function totalCount(): int
//    {
//        $totalCount = 0;
//        foreach ($this->counters as $counter) {
//            $totalCount += $counter->totalCount();
//        }
//        return $totalCount;
//    }

//    public function __toString(): string
//    {
//        $header = ' all against-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
//        $lines = '';
//        foreach ($this->counters as $counter) {
//            $lines .= $counter;
//        }
//
//        return $header . $lines;
//    }
}
