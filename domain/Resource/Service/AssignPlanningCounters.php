<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\Sports\SportWithNrOfFields;

class AssignPlanningCounters
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, NrOfGamesAndUniquePlacesCounterForSport>
     */
    protected array $counterForSportMap = [];

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
                $this->counterForSportMap[$sportNr] = new NrOfGamesAndUniquePlacesCounterForSport(
                    new SportWithNrOfFields( $game->getSport()->sport, $game->getSport()->getNrOfFields()
                    ));
            }
            $this->counterForSportMap[$sportNr]->addGame($game);
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
     * @return array<int, NrOfGamesAndUniquePlacesCounterForSport>
     */
    public function getCounterForSportMap(): array
    {
        return $this->counterForSportMap;
    }

//    /**
//     * @return array<string, PlaceGameCounter>
//     */
//    public function getPlaceInfoMap(): array
//    {
//        return $this->placeGameCounters;
//    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }

//    public function isEmpty(): bool
//    {
//        return count($this->counterForSportMap) === 0;
//    }
}
