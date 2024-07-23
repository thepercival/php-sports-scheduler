<?php

declare(strict_types=1);

namespace SportsScheduler\Resource;

use SportsPlanning\Counters\CounterForPoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Poule;
use SportsScheduler\Place\GameCounter;

class UniquePlacesCounter
{
    protected CounterForPoule $gamePlacesCounter;
    /**
     * @var array<int, bool> $places
     */
    protected array $places = [];

    public function __construct(Poule $poule)
    {
        $this->gamePlacesCounter = new CounterForPoule($poule);
    }

    public function getPoule(): Poule
    {
        return $this->gamePlacesCounter->getPoule();
    }

    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->gamePlacesCounter = $this->gamePlacesCounter->increment();
        foreach ($game->getPlaces() as $gamePlace) {
            if (array_key_exists($gamePlace->getPlace()->getPlaceNr(), $this->places)) {
                continue;
            }
            $this->places[$gamePlace->getPlace()->getPlaceNr()] = true;
        }
    }

    public function getNrOfDistinctPlacesAssigned(): int
    {
        return count($this->places);
    }

    public function getNrOfGames(): int
    {
        return $this->gamePlacesCounter->count();
    }
}
