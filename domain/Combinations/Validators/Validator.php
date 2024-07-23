<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

abstract class Validator
{
    protected AgainstSportVariant $sportVariant;
    /**
     * @var array<int, PlaceCounterMap>
     */
    protected array $placeCounterMaps = [];

    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        $sportVariant = $this->sport->createVariant();
        if (!($sportVariant instanceof AgainstSportVariant)) {
            throw new \Exception('only against-sports', E_ERROR);
        }
        $this->sportVariant = $sportVariant;

        $this->initCounters();
    }

    private function initCounters(): void
    {
        foreach( $this->poule->getPlaces() as $placeA ) {
            $placeCounters = [];
            foreach( $this->poule->getPlaces() as $placeB ) {
                if( $placeA === $placeB ) {
                    continue;
                }
                $placeCounters[$placeB->getPlaceNr()] = new CounterForPlace($placeB);
            }
            $this->placeCounterMaps[$placeA->getPlaceNr()] = new PlaceCounterMap($placeCounters);
        }
    }

    public function getPlaceCombination(AgainstGame $game, AgainstSide $side): PlaceCombination
    {
        $poulePlaces = array_values( array_map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        }, $game->getSidePlaces($side)->toArray() ) );
        return new PlaceCombination($poulePlaces);
    }

    public function addGames(Planning $planning): void
    {
        foreach ($planning->getAgainstGamesForPoule($this->poule) as $game) {
            $this->addGame($game);
        }
    }

    public function balanced(): bool
    {
        foreach ($this->placeCounterMaps as $placeCounterMap) {
            if( $placeCounterMap->calculateReport()->getAmountDifference() > 0 ) {
                return false;
            }
        }
        return true;
    }

    abstract public function addGame(AgainstGame $game): void;
}
