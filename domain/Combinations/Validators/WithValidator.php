<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class WithValidator extends ValidatorAbstract
{
    protected WithNrCounterMap $withNrCounterMap;

    public function __construct()
    {
        parent::__construct();
    }

    public function balanced(): bool
    {
        return $this->duoPlaceNrCounterMapIsBalanced($this->withNrCounterMap);
    }

//    /**
//     * @param Place $place
//     * @param list<Place> $places
//     * @param int $nrOfPlaces
//     * @return list<PlaceCombination>
//     */
//    protected function getWithCombinations(Place $place, array $places, int $nrOfPlaces): array
//    {
//        $placesMinPlace = array_values(array_filter($places, function (Place $placeIt) use ($place): bool {
//            return $placeIt !== $place;
//        }));
//        $combinationIt = new CombinationIt($placesMinPlace, $nrOfPlaces - 1);
//        /** @var array<int, list<Place>> $allCombinations */
//        $allCombinations = $combinationIt->toArray();
//        return array_values(array_map(function (array $combinations): PlaceCombination {
//            return new PlaceCombination($combinations);
//        }, $allCombinations));
//    }


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
