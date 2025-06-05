<?php

declare(strict_types=1);

namespace SportsScheduler\Resource;

use SportsPlanning\Counters\CounterForPoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Poule;
use SportsScheduler\Place\GameCounter;

final class UniquePlacesCounter
{
    protected CounterForPoule $gameCounter;
    /**
     * @var array<int, bool> $places
     */
    protected array $places = [];

    public function __construct(Poule $poule)
    {
        $this->gameCounter = new CounterForPoule($poule);
    }

    public function getPoule(): Poule
    {
        return $this->gameCounter->getPoule();
    }

    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->gameCounter = $this->gameCounter->increment();
        foreach ($game->getPlaceNrs() as $placeNr) {
            if (array_key_exists($placeNr, $this->places)) {
                continue;
            }
            $this->places[$placeNr] = true;
        }
    }

    public function getNrOfDistinctPlacesAssigned(): int
    {
        return count($this->places);
    }

    public function getNrOfGames(): int
    {
        return $this->gameCounter->count();
    }
}
