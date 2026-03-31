<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsScheduler\Combinations\Validators;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsScheduler\Permutations\Iterators\CombinationsIterator;

final class WithValidator extends ValidatorAbstract
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);
    }

    /**
     * @param int $placeNr
     * @param list<int> $placeNrs
     * @param int $nrOfPlaces
     * @return list<PlaceNrCombination>
     */
    protected function getWithCombinations(int $placeNr, array $placeNrs, int $nrOfPlaces): array
    {
        $placeNrsMinPlace = array_values(array_filter($placeNrs, function (int $placeNrIt) use ($placeNr): bool {
            return $placeNrIt !== $placeNr;
        }));
        $combinationIt = new CombinationsIterator($placeNrsMinPlace, $nrOfPlaces - 1);
        /** @var array<int, list<int>> $allCombinations */
        $allCombinations = $combinationIt->rewindAndExport();
        return array_values(array_map(function (array $combinations): PlaceNrCombination {
            return new PlaceNrCombination($combinations);
        }, $allCombinations));
    }

    #[\Override]
    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }
        $homePlaceNrCombination = $this->getPlaceNrCombination($game, AgainstSide::Home);
        $awayPlaceNrCombination = $this->getPlaceNrCombination($game, AgainstSide::Away);

        $this->addCombinations($homePlaceNrCombination);
        $this->addCombinations($awayPlaceNrCombination);
    }

    private function addCombinations(PlaceNrCombination $placeNrCombination): void
    {
        $placeNrsA = $placeNrCombination->getPlaceNrs();
        $placeNrsB = $placeNrCombination->getPlaceNrs();
        foreach ($placeNrsA as $placeNrA) {
            foreach ($placeNrsB as $placeNrB) {
                if( $placeNrA === $placeNrB ) {
                    continue;
                }
                $placeNrCounterMapA = $this->placeNrCounterMaps[$placeNrA];
                $this->placeNrCounterMaps[$placeNrA] = $placeNrCounterMapA->addPlaceNr($placeNrB);

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
