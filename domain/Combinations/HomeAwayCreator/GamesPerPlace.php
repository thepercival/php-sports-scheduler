<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayCreator;

use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\HomeAway;
use SportsScheduler\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

final class GamesPerPlace extends HomeAwayCreator
{
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $gameCounterMap = [];
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $homeCounterMap = [];

    protected int $minNrOfHomeGamesPerPlace = 0;
    protected int $nrOfGamesPerPlace = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param AgainstGppWithPoule $againstGppWithPoule
     * @return list<HomeAway>
     */
    public function create(AgainstGppWithPoule $againstGppWithPoule): array
    {
        $poule = $againstGppWithPoule->getPoule();
        $againstGpp = $againstGppWithPoule->getSportVariant();
        $this->initCounters($againstGppWithPoule->getPoule());
        $this->nrOfGamesPerPlace = $againstGpp->getNrOfGamesPerPlace();
        $this->minNrOfHomeGamesPerPlace = (int)floor($this->nrOfGamesPerPlace / 2);

        $homeAways = [];

        /** @var \Iterator<string, list<Place>> $homeIt */
        $homeIt = new CombinationIt($poule->getPlaceList(), $againstGpp->getNrOfHomePlaces());
        while ($homeIt->valid()) {
            $homePlaceCombination = new PlaceCombination($homeIt->current());
            $awayPlaces = array_diff($poule->getPlaceList(), $homeIt->current());
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

    /**
     * @param Poule $poule
     */
    protected function initCounters(Poule $poule): void
    {
        $this->gameCounterMap = [];
        $this->homeCounterMap = [];
        foreach ($poule->getPlaces() as $place) {
            $this->gameCounterMap[$place->getPlaceNr()] = new PlaceCounter($place);
            $this->homeCounterMap[$place->getPlaceNr()] = new PlaceCounter($place);
        }
    }

    protected function createHomeAway(
        AgainstGpp $sportVariant,
        PlaceCombination $home,
        PlaceCombination $away
    ): HomeAway {
        if ($this->shouldSwap($sportVariant, $home, $away)) {
            foreach ($home->getPlaces() as $homePlace) {
                $this->gameCounterMap[$homePlace->getPlaceNr()]->increment();
            }
            foreach ($away->getPlaces() as $awayPlace) {
                $this->gameCounterMap[$awayPlace->getPlaceNr()]->increment();
                $this->homeCounterMap[$awayPlace->getPlaceNr()]->increment();
            }
            return new HomeAway($away, $home);
        }
        foreach ($home->getPlaces() as $homePlace) {
            $this->gameCounterMap[$homePlace->getPlaceNr()]->increment();
            $this->homeCounterMap[$homePlace->getPlaceNr()]->increment();
        }
        foreach ($away->getPlaces() as $awayPlace) {
            $this->gameCounterMap[$awayPlace->getPlaceNr()]->increment();
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
