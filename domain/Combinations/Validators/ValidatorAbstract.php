<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCounter;
use SportsPlanning\Combinations\PlaceNrCounterMap;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

abstract class ValidatorAbstract
{
    protected AgainstSportVariant $sportVariant;
    /**
     * @var array<int, PlaceNrCounterMap>
     */
    protected array $placeNrCounterMaps = [];

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
        foreach( $this->poule->getPlaceNrs() as $placeNrA ) {
            $placeCounters = [];
            foreach( $this->poule->getPlaceNrs() as $placeNrB ) {
                if( $placeNrA === $placeNrB ) {
                    continue;
                }
                $placeCounters[$placeNrB] = new PlaceNrCounter($placeNrB);
            }
            $this->placeNrCounterMaps[$placeNrA] = new PlaceNrCounterMap($placeCounters);
        }
    }

    public function getPlaceNrCombination(AgainstGame $game, AgainstSide $side): PlaceNrCombination
    {
        $poulePlaceNrs = array_values( array_map(function (AgainstGamePlace $gamePlace): int {
            return $gamePlace->getPlace()->getPlaceNr();
        }, $game->getSidePlaces($side)->toArray() ) );
        return new PlaceNrCombination($poulePlaceNrs);
    }

    public function addGames(Planning $planning): void
    {
        foreach ($planning->getAgainstGamesForPoule($this->poule) as $game) {
            $this->addGame($game);
        }
    }

    public function balanced(): bool
    {
        foreach ($this->placeNrCounterMaps as $placeCounterMap) {
            if( $placeCounterMap->getAmountDifference() > 0 ) {
                return false;
            }
        }
        return true;
    }

    abstract public function addGame(AgainstGame $game): void;
}
