<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayGenerators;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsScheduler\Combinations\DuoPlaceNrIterator;
use SportsScheduler\Combinations\PlaceNrIterator;

final class H2hHomeAwayGenerator
{
    private bool $swap = false;

    /**
     * @param int $nrOfPlaces
     * @return list<OneVsOneHomeAway>
     */
    public function createForOneH2h(int $nrOfPlaces): array
    {
        $homeAways = [];
        $nrOfPlacesRange = new SportRange(1, $nrOfPlaces);
        $duoPlaceNrIt = new DuoPlaceNrIterator($nrOfPlacesRange);
        while( $duoPlaceNrIt->valid() ) {
            $duoPlaceNr = $duoPlaceNrIt->current();
            if( $duoPlaceNr !== null ) {
                $homeAways[] = $this->createHomeAway($duoPlaceNr);
            }
        }
        return $this->swap($homeAways);
    }

    protected function createHomeAway(DuoPlaceNr $duoePlaceNr): OneVsOneHomeAway
    {
        $homeAway = new OneVsOneHomeAway($duoePlaceNr);
        if ($this->shouldSwap($duoePlaceNr->placeNrOne, $duoePlaceNr->placeNrTwo)) {
            return $homeAway->swap();
        }
        return $homeAway;
    }

    protected function shouldSwap(int $homePlaceNr, int $awayPlaceNr): bool
    {
        $even = (($homePlaceNr + $awayPlaceNr) % 2) === 0;
        if ($even && $homePlaceNr < $awayPlaceNr) {
            return true;
        }
        if (!$even && $homePlaceNr > $awayPlaceNr) {
            return true;
        }
        return false;
    }

    /**
     * @param list<OneVsOneHomeAway> $homeAways
     * @return list<OneVsOneHomeAway>
     */
    protected function swap(array $homeAways): array
    {
        if ($this->swap === true) {
            $homeAways = $this->swapHomeAways($homeAways);
        }
        $this->swap = !$this->swap;
        return $homeAways;
    }

    /**
     * @param list<OneVsOneHomeAway> $homeAways
     * @return list<OneVsOneHomeAway>
     */
    private function swapHomeAways(array $homeAways): array
    {
        $swapped = [];
        foreach ($homeAways as $homeAway) {
            $swapped[] = $homeAway->swap();
        }
        return $swapped;
    }
}
