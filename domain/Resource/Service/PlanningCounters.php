<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Resource\GameCounter\GameCounterForPlace;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFields;

final class PlanningCounters
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, NrOfGamesAndUniquePlacesCounterForSport>
     */
    protected array $counterForSportMap = [];

    /**
     * @var array<string, GameCounterForPlace> $placeGameCounters
     */
    protected array $placeGameCounters = [];

    /**
     * @param array<int, Poule> $pouleMap
     * @param list<TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields> $sportsWithNrAndFields
     * @param list<AgainstGame|TogetherGame> $games
     */
    public function __construct(private array $pouleMap, array $sportsWithNrAndFields, array $games)
    {
        $this->init($sportsWithNrAndFields, $games);
    }

    /**
     * @param list<TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields> $sportsWithNrAndFields
     * @param list<TogetherGame|AgainstGame> $games
     */
    private function init(array $sportsWithNrAndFields, array $games): void
    {
        foreach ($sportsWithNrAndFields as $sportWithNrAndFields) {
            $sportNr = $sportWithNrAndFields->sportNr;
            $this->counterForSportMap[$sportNr] = new NrOfGamesAndUniquePlacesCounterForSport(
                $this->pouleMap,
                new SportWithNrOfFields($sportWithNrAndFields->sport, count($sportWithNrAndFields->fields))
            );
        }

        foreach ($games as $game) {
            if( !array_key_exists($game->pouleNr, $this->pouleMap ) ) {
                throw new \Exception('could not found pouleNr');
            }
            $poule = $this->pouleMap[$game->pouleNr];
            $sportNr = $game->getField()->sportNr;
            $this->counterForSportMap[$sportNr]->addGame($game);
            $this->nrOfGames++;

            foreach ($poule->getPlaces($game) as $place) {
                if (!isset($this->placeGameCounters[$place->getUniqueIndex()])) {
                    $this->placeGameCounters[$place->getUniqueIndex()] = new GameCounterForPlace($place, 1);
                } else {
                    $placeGameCounter = $this->placeGameCounters[$place->getUniqueIndex()];
                    $this->placeGameCounters[$place->getUniqueIndex()] = $placeGameCounter->increment();
                }
            }
        }
    }

    /**
     * @return list<NrOfGamesAndUniquePlacesCounterForSport>
     */
    public function getCountersForSports(): array
    {
        return array_values($this->counterForSportMap);
    }

    /**
     * @return list<GameCounterForPlace>
     */
    public function getPlaceGameCounters(): array
    {
        return array_values($this->placeGameCounters);
    }

    public function getNrOfGames(Place|null $place = null): int
    {
        if( $place !== null ) {
            if( !array_key_exists($place->getUniqueIndex(), $this->counterForSportMap) ) {
                return 0;
            }
            return $this->placeGameCounters[$place->getUniqueIndex()]->getNrOfGames();
        }
        return $this->nrOfGames;
    }

//    public function isEmpty(): bool
//    {
//        return count($this->counterForSportMap) === 0;
//    }
}
