<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validator;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Combinations\PlaceCombination;
use SportsScheduler\Combinations\Validator;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class With extends Validator
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);
    }

    /**
     * @param Place $place
     * @param list<Place> $places
     * @param int $nrOfPlaces
     * @return list<PlaceCombination>
     */
    protected function getWithCombinations(Place $place, array $places, int $nrOfPlaces): array
    {
        $placesMinPlace = array_values(array_filter($places, function (Place $placeIt) use ($place): bool {
            return $placeIt !== $place;
        }));
        $combinationIt = new CombinationIt($placesMinPlace, $nrOfPlaces - 1);
        /** @var array<int, list<Place>> $allCombinations */
        $allCombinations = $combinationIt->toArray();
        return array_values(array_map(function (array $combinations): PlaceCombination {
            return new PlaceCombination($combinations);
        }, $allCombinations));
    }

    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }
        $homePlaceCombination = $this->getPlaceCombination($game, Side::Home);
        $awayPlaceCombination = $this->getPlaceCombination($game, Side::Away);

        $this->addCombinations($homePlaceCombination);
        $this->addCombinations($awayPlaceCombination);
    }

    private function addCombinations(PlaceCombination $placeCombination): void
    {
        $placesA = $placeCombination->getPlaces();
        $placesB = $placeCombination->getPlaces();
        foreach ($placesA as $placeA) {
            foreach ($placesB as $placeB) {
                if( $placeA === $placeB ) {
                    continue;
                }
                $placeCounterMapA = $this->placeCounterMaps[$placeA->getPlaceNr()];
                $this->placeCounterMaps[$placeA->getPlaceNr()] = $placeCounterMapA->addPlace($placeB);

//                $placeCounterMapB = $this->placeCounterMaps[$placeB->getPlaceNr()];
//                $this->placeCounterMaps[$placeB->getPlaceNr()] = $placeCounterMapB->addPlace($placeA);
            }
        }
    }


//
//    public function totalCount(): int
//    {
//        $totalCount = 0;
//        foreach ($this->counters as $counter) {
//            $totalCount += $counter->totalCount();
//        }
//        return $totalCount;
//    }
//
//    public function __toString(): string
//    {
//        $header = ' all with-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
//        $lines = '';
//        foreach ($this->counters as $counter) {
//            $lines .= $counter;
//        }
//
//        return $header . $lines;
//    }
}
