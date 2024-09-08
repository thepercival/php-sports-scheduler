<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayGenerators;

use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\SportRange;
use SportsHelpers\SportVariants\AgainstGpp;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsScheduler\Combinations\DuoPlaceNrIterator;
use SportsScheduler\Combinations\PlaceNrIterator;

final class GppHomeAwayGenerator
{
    protected AmountNrCounterMap $amountNrCounterMapCumulative;
    protected SideNrCounterMap $homeNrCounterMapCumulative;

    private bool $swap = false;


    public function __construct(int $nrOfPlaces)
    {
        $this->amountNrCounterMapCumulative = new AmountNrCounterMap($nrOfPlaces);
        $this->homeNrCounterMapCumulative = new SideNrCounterMap(Side::Home, $nrOfPlaces);
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function create(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        if( $againstGpp->nrOfHomePlaces === 1 && $againstGpp->nrOfAwayPlaces === 1) {
            return $this->createOneVsOne($againstGppWithNrOfPlaces);
        } else if( $againstGpp->nrOfHomePlaces === 1 && $againstGpp->nrOfAwayPlaces === 2) {
            return $this->createOneVsTwo($againstGppWithNrOfPlaces);
        } else if( $againstGpp->nrOfHomePlaces === 2 && $againstGpp->nrOfAwayPlaces === 2) {
            return $this->createTwoVsTwo($againstGppWithNrOfPlaces);
        }
        throw new \Exception('invalid nrOfHomePlace-nrOfAwayPlaces');
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function createOneVsOne(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        $homeAways = [];
        $sportRange =(new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()));
        $duoPlaceNrIt = new DuoPlaceNrIterator($sportRange);
        while( $duoPlaceNr = $duoPlaceNrIt->current()) {

            $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrOne, $duoPlaceNr->placeNrOne);
            if ($this->shouldSwapOneVsOne($againstGpp, $homeAway)) {
                $homeAway = $homeAway->swap();
            }
            $this->addToCounters($homeAway);
            $homeAways[] = $homeAway;

            $duoPlaceNrIt->next();
        }

        return $this->swap($homeAways);
    }



    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function createOneVsTwo(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        $homeAways = [];
        $sportRange =(new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()));
        $homePlaceNrIt = new PlaceNrIterator($sportRange);
        while( $homePlaceNr = $homePlaceNrIt->current()) {
            $awayDuoPlaceNrIt = new DuoPlaceNrIterator($sportRange, [$homePlaceNr]);
            while( $awayDuoPlaceNr = $awayDuoPlaceNrIt->current()) {

                $homeAway = new OneVsTwoHomeAway($homePlaceNr, $awayDuoPlaceNr);
                if ($this->shouldSwapOneVsTwo($againstGpp, $homeAway)) {
                    // $homeAway = $homeAway->swap();
                }
                $this->addToCounters($homeAway);
                $homeAways[] = $homeAway;

                $awayDuoPlaceNrIt->next();
            }
        }
        // return $this->swap($homeAways);
        return $homeAways;
    }

    protected function addToCounters( OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void {
        $this->amountNrCounterMapCumulative->addHomeAway($homeAway);
        $this->homeNrCounterMapCumulative->addHomeAway($homeAway);
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function createTwoVsTwo(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        $homeAways = [];
        $sportRange = new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces());

        $homeDuoPlaceNrsIt = new DuoPlaceNrIterator($sportRange);
        while ($homeDuoPlaceNr = $homeDuoPlaceNrsIt->current()) {
            $awayDuoPlaceNrsIt = new DuoPlaceNrIterator($sportRange, $homeDuoPlaceNr->getPlaceNrs());
            while ($awayDuoPlaceNr = $awayDuoPlaceNrsIt->current()) {
                $homeAway = new TwoVsTwoHomeAway($homeDuoPlaceNr, $awayDuoPlaceNr );
                if ($this->shouldSwapTwoVsTwo($againstGpp, $homeAway)) {
                    $homeAway = $homeAway->swap();
                }
                $this->addToCounters($homeAway);
                $homeAways[] = $homeAway;

                $awayDuoPlaceNrsIt->next();
            }
            $homeDuoPlaceNrsIt->next();
        }
        return $this->swap($homeAways);
    }

    protected function shouldSwapOneVsOne(AgainstGpp $againstGpp, OneVsOneHomeAway $homeAway): bool
    {
        if ($againstGpp->nrOfHomePlaces === 1) {
            return $this->arePlaceNumbersEqualOrUnequal($homeAway);
        }
        if ($this->mustBeHome($againstGpp, $homeAway->getHome())) {
            return false;
        }
        if ($this->mustBeHome($againstGpp, $homeAway->getAway())) {
            return true;
        }
        return $this->getNrOfHomeGames($homeAway->getHome()) > $this->getNrOfHomeGames($homeAway->getAway());
    }

    protected function shouldSwapOneVsTwo(AgainstGpp $againstGpp, OneVsTwoHomeAway $homeAway): bool
    {
        throw new \Exception('should swap but 1vs2 can not be swapped');
    }

    protected function shouldSwapTwoVsTwo(AgainstGpp $againstGpp, TwoVsTwoHomeAway $homeAway): bool
    {
        if ($this->mustBeHome($againstGpp, $homeAway->getHome())) {
            return false;
        }
        if ($this->mustBeHome($againstGpp, $homeAway->getAway())) {
            return true;
        }
        return $this->getNrOfHomeGames($homeAway->getHome()) > $this->getNrOfHomeGames($homeAway->getAway());
    }


    /**
     * @TODO CDK This function can be removed if swapping for OneVsTwo is indeed not necesarry
     *
     * @param OneVsOneHomeAway $homeAway
     * @return bool
     */
    protected function arePlaceNumbersEqualOrUnequal(OneVsOneHomeAway $homeAway): bool
    {
        $home = $homeAway->getHome();
        $away = $homeAway->getAway();
        return (($this->sumPlaceNrs($home) % 2) === 1 && ($this->sumPlaceNrs($away) % 2) === 1)
            || (($this->sumPlaceNrs($home) % 2) === 0 && ($this->sumPlaceNrs($away) % 2) === 0);
    }

    protected function sumPlaceNrs(int|DuoPlaceNr $duoPlaceNr): int
    {
        if( $duoPlaceNr instanceof DuoPlaceNr) {
            return array_sum($duoPlaceNr->getPlaceNrs());
        }
        return $duoPlaceNr;
    }

    protected function mustBeHome(AgainstGpp $againstGpp, int|DuoPlaceNr $duoPlaceNr): bool
    {
        $minNrOfHomeGamesPerPlace = $this->getMinNrOfHomeGamesPerPlace($againstGpp);
        $placeNrs = ($duoPlaceNr instanceof DuoPlaceNr) ? $duoPlaceNr->getPlaceNrs() : [$duoPlaceNr];
        foreach ($placeNrs as $placeNr) {
            $nrOfGames = $this->amountNrCounterMapCumulative->count($placeNr);
            $nrOfHomeGames = $this->homeNrCounterMapCumulative->count($placeNr);
            $nrOfGamesLeft = $againstGpp->nrOfGamesPerPlace - $nrOfGames;
            if ($nrOfGamesLeft === ($minNrOfHomeGamesPerPlace - $nrOfHomeGames)) {
                return true;
            }
        }
        return false;
    }

    protected function getNrOfHomeGames(int|DuoPlaceNr $duoPlaceNr): int
    {
        if( $duoPlaceNr instanceof DuoPlaceNr) {
            $placeNrs = $duoPlaceNr->getPlaceNrs();
        } else {
            $placeNrs = [$duoPlaceNr];
        }
        return array_sum( array_map( function(int $placeNr): int {
            return $this->amountNrCounterMapCumulative->count($placeNr);
        }, $placeNrs) );

    }

//    protected function getNrOfGames(PlaceCombination $placeCombination): int
//    {
//        $nrOfGames = 0;
//        foreach ($placeCombination->getPlaces() as $place) {
//            $nrOfGames += $this->getNrOfGamesForPlace($place);
//        }
//        return $nrOfGames;
//    }

    private function getMinNrOfHomeGamesPerPlace(AgainstGpp $againstGpp): int
    {
        return (int)floor($againstGpp->nrOfGamesPerPlace / 2);
    }

    /**
     * @param list<OneVsOneHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|TwoVsTwoHomeAway>
     */
    protected function swap(array $homeAways): array
    {
        $swap = $this->swap;
        $this->swap = !$swap;
        if ($swap) {
            $swapped = [];
            foreach ($homeAways as $homeAway) {
                $swapped[] = $homeAway->swap();
            }
            return $swapped;
        }
        return $homeAways;
    }
}
