<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;

class InfoToAssign
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, SportInfo>
     */
    protected array $sportInfoMap = [];

    /**
     * @var array<string, PlaceGameCounter> $placeGameCounters
     */
    protected array $placeGameCounters = [];

    /**
     * @param list<AgainstGame|TogetherGame> $games
     */
    public function __construct(array $games)
    {
        $this->init($games);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     */
    private function init(array $games): void
    {
        foreach ($games as $game) {
            $sportNr = $game->getSport()->getNumber();
            if (!isset($this->sportInfoMap[$sportNr])) {
                $this->sportInfoMap[$sportNr] = new SportInfo($game->getSport());
            }
            $this->sportInfoMap[$sportNr]->addGame($game);
            $this->nrOfGames++;

            foreach ($game->getPlaces() as $gamePlace) {
                $place = $gamePlace->getPlace();
                if (!isset($this->placeGameCounters[$place->getUniqueIndex()])) {
                    $this->placeGameCounters[$place->getUniqueIndex()] = new PlaceGameCounter($place, 1);
                } else {
                    $placeGameCounter = $this->placeGameCounters[$place->getUniqueIndex()];
                    $this->placeGameCounters[$place->getUniqueIndex()] = $placeGameCounter->increment();
                }
            }
        }
    }

    /**
     * @return array<int, SportInfo>
     */
    public function getSportInfoMap(): array
    {
        return $this->sportInfoMap;
    }

    /**
     * @return array<string, PlaceGameCounter>
     */
    public function getPlaceInfoMap(): array
    {
        return $this->placeGameCounters;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }

    public function isEmpty(): bool
    {
        return count($this->sportInfoMap) === 0;
    }
}
