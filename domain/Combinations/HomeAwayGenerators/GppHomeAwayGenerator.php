<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayGenerators;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class GppHomeAwayGenerator
{
    protected AmountNrCounterMap $amountNrCounterMapCumulative;
    protected SideNrCounterMap $homeNrCounterMapCumulative;

    private bool $swap = false;


    public function __construct()
    {
        $this->amountNrCounterMapCumulative = new AmountNrCounterMap();
        $this->homeNrCounterMapCumulative = new SideNrCounterMap(Side::Home);
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function create(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        if( $againstGpp->getNrOfHomePlaces() === 1 && $againstGpp->getNrOfAwayPlaces() === 1) {
            return $this->createOneVsOne($againstGppWithNrOfPlaces);
        } else if( $againstGpp->getNrOfHomePlaces() === 1 && $againstGpp->getNrOfAwayPlaces() === 2) {
            return $this->createOneVsTwo($againstGppWithNrOfPlaces);
        } else if( $againstGpp->getNrOfHomePlaces() === 2 && $againstGpp->getNrOfAwayPlaces() === 2) {
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
        $placeNrs = (new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()))->toArray();
        for( $homePlaceNr = 1 ; $homePlaceNr <= count($placeNrs) ; $homePlaceNr++ ) {
            $awayPlaceNrs = array_diff($placeNrs, $homePlaceNr);
            foreach( $awayPlaceNrs as $awayPlaceNr ) {
                if ( $homePlaceNr < $awayPlaceNr) {
                    $homeAway = $this->createOneVsOneHomeAway($againstGpp, $homePlaceNr, $awayPlaceNr);
                    $homeAways[] = $homeAway;
                }
            }
        }
        return $this->swap($homeAways);
    }

    protected function createOneVsOneHomeAway(AgainstGpp $sportVariant, int $homePlaceNr, int $awayPlaceNr): OneVsOneHomeAway {
        $homeAway = new OneVsOneHomeAway($homePlaceNr, $awayPlaceNr );
        if ($this->shouldSwap($sportVariant, $homePlaceNr, $awayPlaceNr)) {
            $homeAway = $homeAway->swap();
        }
        $this->amountNrCounterMapCumulative->addHomeAway($homeAway);
        $this->homeNrCounterMapCumulative->addHomeAway($homeAway);
        return $homeAway;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function createOneVsTwo(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        $homeAways = [];
        $placeNrs = (new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()))->toArray();
        for( $homePlaceNr = 1 ; $homePlaceNr <= count($placeNrs) ; $homePlaceNr++ ) {
            $awayPlaceNrs = array_diff($placeNrs, $homePlaceNr);
            /** @var \Iterator<string, list<int>> $awayPlaceNrsIt */
            $awayDuoPlaceNrIt = new CombinationIt($awayPlaceNrs, $againstGpp->getNrOfAwayPlaces());
            while ($awayDuoPlaceNrIt->valid()) {
                $awayPlaceNrs = $awayDuoPlaceNrIt->current();
                $awayDuoPlaceNr = new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]);
                $homeAway = $this->createOneVsTwoHomeAway($againstGpp, $homePlaceNr, $awayDuoPlaceNr);
                $homeAways[] = $homeAway;
                $awayDuoPlaceNrIt->next();
            }
        }
        return $this->swap($homeAways);
    }

    protected function createOneVsTwoHomeAway(AgainstGpp $sportVariant, int $homePlaceNr, DuoPlaceNr $awayDuoPlaceNr): OneVsTwoHomeAway {
        $homeAway = new OneVsTwoHomeAway($homePlaceNr, $awayDuoPlaceNr );
        if ($this->shouldSwap($sportVariant, $homePlaceNr, $awayDuoPlaceNr)) {
//            $homeAway = $homeAway->swap();
            throw new \Exception('should swap but 1vs2 can not be swapped');
        }
        $this->amountNrCounterMapCumulative->addHomeAway($homeAway);
        $this->homeNrCounterMapCumulative->addHomeAway($homeAway);
        return $homeAway;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function createTwoVsTwo(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();

        $homeAways = [];
        $placeNrs = (new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()))->toArray();

        /** @var \Iterator<int, list<int>> $homePlaceNrsIt */
        $homePlaceNrsIt = new CombinationIt($placeNrs, $againstGpp->getNrOfHomePlaces());
        while ($homePlaceNrsIt->valid()) {
            $homePlaceNrs = $homePlaceNrsIt->current();
            $homeDuoPlaceNr = new DuoPlaceNr($homePlaceNrs[0], $homePlaceNrs[1]);

            $awayPlaceNrs = array_diff($homePlaceNrs, $homePlaceNrsIt->current());
            /** @var \Iterator<string, list<int>> $awayPlaceNrsIt */
            $awayPlaceNrsIt = new CombinationIt($awayPlaceNrs, $againstGpp->getNrOfAwayPlaces());
            while ($awayPlaceNrsIt->valid()) {
                $awayPlaceNrs = $awayPlaceNrsIt->current();
                $awayDuoPlaceNr = new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]);

                if ($homeDuoPlaceNr->createUniqueNumber() < $awayDuoPlaceNr->createUniqueNumber()) {
                    $homeAway = $this->createTwoVsTwoHomeAway($againstGpp, $homeDuoPlaceNr, $awayDuoPlaceNr);
                    $homeAways[] = $homeAway;
                }
                $awayPlaceNrsIt->next();
            }
            $homePlaceNrsIt->next();
        }
        return $this->swap($homeAways);
    }

    protected function createTwoVsTwoHomeAway(AgainstGpp $sportVariant, DuoPlaceNr $homeDuoPlaceNr, DuoPlaceNr $awayDuoPlaceNr): TwoVsTwoHomeAway {
        $homeAway = new TwoVsTwoHomeAway($homeDuoPlaceNr, $awayDuoPlaceNr );
        if ($this->shouldSwap($sportVariant, $homeDuoPlaceNr, $awayDuoPlaceNr)) {
            $homeAway = $homeAway->swap();
        }
        $this->amountNrCounterMapCumulative->addHomeAway($homeAway);
        $this->homeNrCounterMapCumulative->addHomeAway($homeAway);
        return $homeAway;
    }

    protected function shouldSwap(AgainstGpp $againstGpp, int|DuoPlaceNr $home, int|DuoPlaceNr $away): bool
    {
        if ($againstGpp->getNrOfHomePlaces() !== $againstGpp->getNrOfAwayPlaces()) {
            return false;
        }
        if ($againstGpp->getNrOfHomePlaces() === 1) {
            return $this->arePlaceNumbersEqualOrUnequal($home, $away);
        }
        if ($this->mustBeHome($againstGpp, $home)) {
            return false;
        }
        if ($this->mustBeHome($againstGpp, $away)) {
            return true;
        }
        return $this->getNrOfHomeGames($home) > $this->getNrOfHomeGames($away);
    }

    protected function arePlaceNumbersEqualOrUnequal(int|DuoPlaceNr $home, int|DuoPlaceNr $away): bool
    {
        return (($this->sumPlaceNrs($home) % 2) === 1 && ($this->sumPlaceNrs($away) % 2) === 1)
            || (($this->sumPlaceNrs($home) % 2) === 0 && ($this->sumPlaceNrs($away) % 2) === 0);
    }

    protected function sumPlaceNrs(int|DuoPlaceNr $duoPlaceNr): int
    {
        if($duoPlaceNr instanceof DuoPlaceNr) {
            return array_sum($duoPlaceNr->getPlaceNrs());
        }
        return $duoPlaceNr;
    }

    protected function mustBeHome(AgainstGpp $againstGpp, int|DuoPlaceNr $duoPlaceNr): bool
    {
        $minNrOfHomeGamesPerPlace = $this->getMinNrOfHomeGamesPerPlace($againstGpp);
        foreach ($duoPlaceNr->getPlaceNrs() as $placeNr) {
            $nrOfGames = $this->getNrOfGamesForPlaceNr($placeNr);
            $nrOfHomeGames = $this->getNrOfHomeGamesForPlaceNr($placeNr);
            $nrOfGamesLeft = $againstGpp->getNrOfGamesPerPlace() - $nrOfGames;
            if ($nrOfGamesLeft === ($minNrOfHomeGamesPerPlace - $nrOfHomeGames)) {
                return true;
            }
        }
        return false;
    }

    protected function getNrOfGamesForPlaceNr(int $placeNr): int
    {
        return $this->amountNrCounterMapCumulative[$placeNr]->count();
    }

    protected function getNrOfHomeGamesForPlaceNr(int $placeNr): int
    {
        return $this->homeNrCounterMapCumulative[$placeNr]->count();
    }

    protected function getNrOfHomeGames(int|DuoPlaceNr $duoPlaceNr): int
    {
        if( $duoPlaceNr instanceof DuoPlaceNr) {
            $placeNrs = $duoPlaceNr->getPlaceNrs();
        } else {
            $placeNrs = [$duoPlaceNr];
        }
        return array_sum( array_map( function(int $placeNr): int {
            return $this->getNrOfHomeGamesForPlaceNr($placeNr);
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
        return (int)floor($againstGpp->getNrOfGamesPerPlace() / 2);
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
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
