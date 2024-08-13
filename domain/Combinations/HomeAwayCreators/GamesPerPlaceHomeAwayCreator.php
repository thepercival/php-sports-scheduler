<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayCreators;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\SportRange;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsScheduler\Combinations\HomeAwayCreators;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class GamesPerPlaceHomeAwayCreator extends HomeAwayCreatorAbstract
{
    protected AmountNrCounterMap $amountNrCounterMap;
    protected SideNrCounterMap $homeNrCounterMap;

    protected int $minNrOfHomeGamesPerPlace = 0;
    protected int $nrOfGamesPerPlace = 0;

    public function __construct()
    {
        $this->amountNrCounterMap = new AmountNrCounterMap();
        $this->homeNrCounterMap = new SideNrCounterMap(Side::Home);
        parent::__construct();
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function create(AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $againstGpp = $againstGppWithNrOfPlaces->getSportVariant();
        $this->nrOfGamesPerPlace = $againstGpp->getNrOfGamesPerPlace();
        $this->minNrOfHomeGamesPerPlace = (int)floor($this->nrOfGamesPerPlace / 2);

        $homeAways = [];
        $placeNrs = (new SportRange(1, $againstGppWithNrOfPlaces->getNrOfPlaces()))->toArray();

        /** @var \Iterator<string, list<Place>> $homeIt */
        $homeIt = new CombinationIt($placeNrs, $againstGpp->getNrOfHomePlaces());
        while ($homeIt->valid()) {
            $homeDuoPlaceNr = new PlaceCombination($homeIt->current());
            $awayPlaces = array_diff($placeNrs, $homeIt->current());
            /** @var \Iterator<string, list<Place>> $awayIt */
            $awayIt = new CombinationIt($awayPlaces, $againstGpp->getNrOfAwayPlaces());
            while ($awayIt->valid()) {
                $awayPlaceCombination = new PlaceCombination($awayIt->current());
                if ($againstGpp->getNrOfHomePlaces() !== $againstGpp->getNrOfAwayPlaces()
                    || $homePlaceCombination->getNumber() < $awayPlaceCombination->getNumber()) {
                    $homeAway = $this->createHomeAway($againstGpp, $homePlaceCombination, $awayPlaceCombination);
                    array_push($homeAways, $homeAway);
                }
                $awayIt->next();
            }
            $homeIt->next();
        }
        return $this->swap($homeAways);
    }

    protected function createHomeAway(
        AgainstGpp $sportVariant,
        PlaceCombination $home,
        PlaceCombination $away
    ): HomeAway {
        if ($this->shouldSwap($sportVariant, $home, $away)) {
            foreach ($home->getPlaces() as $homePlace) {
                $gameCounter = $this->gameCounterMap[$homePlace->getPlaceNr()];
                $this->gameCounterMap[$homePlace->getPlaceNr()] = $gameCounter->increment();
            }
            foreach ($away->getPlaces() as $awayPlace) {
                $gameCounter = $this->gameCounterMap[$awayPlace->getPlaceNr()];
                $this->gameCounterMap[$awayPlace->getPlaceNr()] = $gameCounter->increment();
                $homeCounter = $this->homeCounterMap[$awayPlace->getPlaceNr()];
                $this->homeCounterMap[$awayPlace->getPlaceNr()] = $homeCounter->increment();
            }
            return new HomeAway($away, $home);
        }
        foreach ($home->getPlaces() as $homePlace) {
            $gameCounter = $this->gameCounterMap[$homePlace->getPlaceNr()];
            $this->gameCounterMap[$homePlace->getPlaceNr()] = $gameCounter->increment();
            $homeCounter = $this->homeCounterMap[$homePlace->getPlaceNr()];
            $this->homeCounterMap[$homePlace->getPlaceNr()] = $homeCounter->increment();
        }
        foreach ($away->getPlaces() as $awayPlace) {
            $gameCounter = $this->gameCounterMap[$awayPlace->getPlaceNr()];
            $this->gameCounterMap[$awayPlace->getPlaceNr()] = $gameCounter->increment();
        }
        return new HomeAway($home, $away);
    }

    protected function shouldSwap(AgainstGpp $sportVariant, PlaceCombination $home, PlaceCombination $away): bool
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

    protected function arePlaceNumbersEqualOrUnequal(PlaceCombination $home, PlaceCombination $away): bool
    {
        return (($this->getPlaceNumbers($home) % 2) === 1 && ($this->getPlaceNumbers($away) % 2) === 1)
            || (($this->getPlaceNumbers($home) % 2) === 0 && ($this->getPlaceNumbers($away) % 2) === 0);
    }

    protected function getPlaceNumbers(PlaceCombination $combination): int
    {
        $number = 0;
        foreach ($combination->getPlaces() as $place) {
            $number += $place->getPlaceNr();
        }
        return $number;
    }

    protected function mustBeHome(PlaceCombination $placeCombination): bool
    {
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames = $this->getNrOfGamesForPlace($place);
            $nrOfHomeGames = $this->getNrOfHomeGamesForPlace($place);
            $nrOfGamesLeft = $this->nrOfGamesPerPlace - $nrOfGames;
            if ($nrOfGamesLeft === ($this->minNrOfHomeGamesPerPlace - $nrOfHomeGames)) {
                return true;
            }
        }
        return false;
    }

    protected function getNrOfGamesForPlace(Place $place): int
    {
        return $this->gameCounterMap[$place->getPlaceNr()]->count();
    }

    protected function getNrOfHomeGamesForPlace(Place $place): int
    {
        return $this->homeCounterMap[$place->getPlaceNr()]->count();
    }

    protected function getNrOfHomeGames(PlaceCombination $placeCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames += $this->getNrOfHomeGamesForPlace($place);
        }
        return $nrOfGames;
    }

    protected function getNrOfGames(PlaceCombination $placeCombination): int
    {
        $nrOfGames = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfGames += $this->getNrOfGamesForPlace($place);
        }
        return $nrOfGames;
    }
}
