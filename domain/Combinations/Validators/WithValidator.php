<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class WithValidator extends ValidatorAbstract
{
    protected WithNrCounterMap $withNrCounterMap;

    public function __construct(int $nrOfPlaces)
    {
        $this->withNrCounterMap = new WithNrCounterMap($nrOfPlaces);
        parent::__construct();
    }

    public function balanced(): bool
    {
        return $this->duoPlaceNrCounterMapIsBalanced($this->withNrCounterMap);
    }

    public function addGame(AgainstGame $game): void
    {
        $homeAway = $game->createHomeAway();
        if ($homeAway instanceof OneVsOneHomeAway) {
            return;
        }
        $this->addHomeAway($homeAway);
    }

    public function addHomeAway(OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->withNrCounterMap->addHomeAway($homeAway);
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
