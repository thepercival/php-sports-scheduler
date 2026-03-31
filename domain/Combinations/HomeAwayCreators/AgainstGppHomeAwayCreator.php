<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayCreators;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCounter;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;
use SportsScheduler\Permutations\Iterators\CombinationsIterator;

final class AgainstGppHomeAwayCreator extends HomeAwayCreatorAbstract
{
    /**
     * @var array<int, PlaceNrCounter>
     */
    protected array $gameCounterMap = [];
    /**
     * @var array<int, PlaceNrCounter>
     */
    protected array $homeCounterMap = [];

    protected int $minNrOfHomeGamesPerPlace = 0;
    protected int $nrOfGamesPerPlace = 0;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<HomeAway>
     */
    public function create(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();
        $this->initCounters($againstGppWithNrOfPlaces->getNrOfPlaces());
        $this->nrOfGamesPerPlace = $againstGpp->getNrOfGamesPerPlace();
        $this->minNrOfHomeGamesPerPlace = (int)floor($this->nrOfGamesPerPlace / 2);

        $homeAways = [];

        $placeNrs = range(1,$againstGppWithNrOfPlaces->getNrOfPlaces());
        /** @var CombinationsIterator<int> $homeIt */
        $homeIt = new CombinationsIterator($placeNrs, $againstGpp->getNrOfHomePlaces());

        while ($homeIt->valid()) {
            $currentHomeIt = $homeIt->customCurrent();
            $awayPlaceNrs = array_values(array_diff($placeNrs, $currentHomeIt));
            /** @var CombinationsIterator<int> $awayIt */
            $awayIt = new CombinationsIterator($awayPlaceNrs, $againstGpp->getNrOfAwayPlaces());
            while ($awayIt->valid()) {
                $awayPlaceNrCombination = new PlaceNrCombination($awayIt->customCurrent());
                $homePlaceNrCombination = new PlaceNrCombination($currentHomeIt);
                if ($againstGpp->getNrOfHomePlaces() !== $againstGpp->getNrOfAwayPlaces()
                    || $homePlaceNrCombination->getNumber() < $awayPlaceNrCombination->getNumber()) {
                    $homeAway = $this->createHomeAway($againstGpp, $homePlaceNrCombination, $awayPlaceNrCombination);
                    array_push($homeAways, $homeAway);
                }
                $awayIt->next();
            }
            $homeIt->next();
        }
        return $this->swap($homeAways);
    }

    protected function initCounters(int $nrOfPlaces): void
    {
        $this->gameCounterMap = [];
        $this->homeCounterMap = [];
        for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            $this->gameCounterMap[$placeNr] = new PlaceNrCounter($placeNr);
            $this->homeCounterMap[$placeNr] = new PlaceNrCounter($placeNr);
        }
    }

    protected function createHomeAway(
        AgainstGpp $sportVariant,
        PlaceNrCombination $home,
        PlaceNrCombination $away
    ): HomeAway {
        if ($this->shouldSwap($sportVariant, $home, $away)) {
            foreach ($home->getPlaceNrs() as $homePlaceNr) {
                $this->gameCounterMap[$homePlaceNr]->increment();
            }
            foreach ($away->getPlaceNrs() as $awayPlaceNr) {
                $this->gameCounterMap[$awayPlaceNr]->increment();
                $this->homeCounterMap[$awayPlaceNr]->increment();
            }
            return new HomeAway($away, $home);
        }
        foreach ($home->getPlaceNrs() as $homePlaceNr) {
            $this->gameCounterMap[$homePlaceNr]->increment();
            $this->homeCounterMap[$homePlaceNr]->increment();
        }
        foreach ($away->getPlaceNrs() as $awayPlaceNr) {
            $this->gameCounterMap[$awayPlaceNr]->increment();
        }
        return new HomeAway($home, $away);
    }

    protected function shouldSwap(AgainstGpp $sportVariant, PlaceNrCombination $home, PlaceNrCombination $away): bool
    {
        if ($sportVariant->getNrOfHomePlaces() !== $sportVariant->getNrOfAwayPlaces()) {
            return false;
        }
        if ($sportVariant->getNrOfHomePlaces() === 1) {
            return $this->arePlaceNumbersEqualOrUnequal($home, $away);
        }
        if ($this->mustBeHome($home)) {
            return false;
        }
        if ($this->mustBeHome($away)) {
            return true;
        }
        return $this->getNrOfHomeGames($home) > $this->getNrOfHomeGames($away);
    }

    protected function arePlaceNumbersEqualOrUnequal(PlaceNrCombination $home, PlaceNrCombination $away): bool
    {
        return (($this->getPlaceNumbers($home) % 2) === 1 && ($this->getPlaceNumbers($away) % 2) === 1)
            || (($this->getPlaceNumbers($home) % 2) === 0 && ($this->getPlaceNumbers($away) % 2) === 0);
    }

    protected function getPlaceNumbers(PlaceNrCombination $combination): int
    {
        $number = 0;
        foreach ($combination->getPlaceNrs() as $placeNr) {
            $number += $placeNr;
        }
        return $number;
    }

    protected function mustBeHome(PlaceNrCombination $placeNrCombination): bool
    {
        foreach ($placeNrCombination->getPlaceNrs() as $placeNr) {
            $nrOfGames = $this->getNrOfGamesForPlace($placeNr);
            $nrOfHomeGames = $this->getNrOfHomeGamesForPlace($placeNr);
            $nrOfGamesLeft = $this->nrOfGamesPerPlace - $nrOfGames;
            if ($nrOfGamesLeft === ($this->minNrOfHomeGamesPerPlace - $nrOfHomeGames)) {
                return true;
            }
        }
        return false;
    }

    protected function getNrOfGamesForPlace(int $placeNr): int
    {
        return $this->gameCounterMap[$placeNr]->count();
    }

    protected function getNrOfHomeGamesForPlace(int $placeNr): int
    {
        return $this->homeCounterMap[$placeNr]->count();
    }

    protected function getNrOfHomeGames(PlaceNrCombination $placeNrCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeNrCombination->getPlaceNrs() as $placeNr) {
            $nrOfGames += $this->getNrOfHomeGamesForPlace($placeNr);
        }
        return $nrOfGames;
    }

    protected function getNrOfGames(PlaceNrCombination $placeNrCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeNrCombination->getPlaceNrs() as $placeNr) {
            $nrOfGames += $this->getNrOfGamesForPlace($placeNr);
        }
        return $nrOfGames;
    }
}
