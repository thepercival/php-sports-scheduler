<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayGenerators;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsScheduler\Combinations\DuoPlaceNrIterator;

final class H2hHomeAwayGenerator
{
    /**
     * @param int $nrOfPlaces
     * @param int $nrOfH2h
     * @return list<OneVsOneHomeAway>
     */
    public function createForOneH2h(int $nrOfPlaces, int $nrOfH2h): array
    {
        $homeAways = [];
        $nrOfPlacesRange = new SportRange(1, $nrOfPlaces);
        $duoPlaceNrIt = new DuoPlaceNrIterator($nrOfPlacesRange);
        while( $duoPlaceNr = $duoPlaceNrIt->current() ) {

            if ($this->shouldSwap($duoPlaceNr)) {
                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrTwo, $duoPlaceNr->placeNrOne);
            } else {
                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrOne, $duoPlaceNr->placeNrTwo);
            }
            if( ($nrOfH2h % 2) === 0 ) {
                $homeAway = $homeAway->swap();
            }
            $homeAways[] = $homeAway;
            $duoPlaceNrIt->next();
        }
        return $homeAways;
    }



    protected function shouldSwap(DuoPlaceNr $duoPlaceNr): bool
    {
        return (($duoPlaceNr->placeNrOne + $duoPlaceNr->placeNrTwo) % 2) === 0;
    }
}
