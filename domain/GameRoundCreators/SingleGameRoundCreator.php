<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Single as SingleWithNrOfPlaces;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule\GameRounds\GameRoundTogetherGamePlace;
use SportsPlanning\Schedule\GameRounds\GameRoundTogetherGame;
use SportsPlanning\Output\Combinations\GameRoundOutput;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class SingleGameRoundCreator
{
    protected GameRoundOutput $gameRoundOutput;
//    /**
//     * @var array<string,array<string,PlaceCounter>>
//     */
//    protected array $assignedTogetherMap = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        int $nrOfPlaces,
        SingleSportVariant $sportVariant,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap
    ): TogetherGameRound {
        $variantWithNrOfPlaces = new SingleWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $gameRound = new TogetherGameRound();
        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithNrOfPlaces->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(int $placeNr) => new GameRoundTogetherGamePlace($gameRoundNumber, $placeNr), $placeNrs);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithNrOfPlaces,
                $amountNrCounterMap,
                $togetherNrCounterMap,
                array_values($gamePlaces),
                $remainingGamePlaces,
                $gameRound
            );
            foreach( $gameRound->convertToPlaceNrs() as $placeNr ) {
                $amountNrCounterMap->addPlaceNr($placeNr);
            }
            foreach( $gameRound->convertToDuoPlaceNrs() as $duoPlaceNr ) {
                $togetherNrCounterMap->addDuoPlaceNr($duoPlaceNr);
            }
            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithNrOfPlaces, $amountNrCounterMap, $togetherNrCounterMap, $remainingGamePlaces, [], $gameRound, true);
            foreach( $gameRound->convertToPlaceNrs() as $placeNr ) {
                $amountNrCounterMap->addPlaceNr($placeNr);
            }
            foreach( $gameRound->convertToDuoPlaceNrs() as $duoPlaceNr ) {
                $togetherNrCounterMap->addDuoPlaceNr($duoPlaceNr);
            }
        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }

    private function updateCounters(): void {

    }

    /**
     * @param SingleWithNrOfPlaces $variantWithNrOfPlaces
     * @param AmountNrCounterMap $amountNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param list<GameRoundTogetherGamePlace> $unSortedGamePlaces
     * @param list<GameRoundTogetherGamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<GameRoundTogetherGamePlace>
     */
    protected function assignGameRound(
        SingleWithNrOfPlaces $variantWithNrOfPlaces,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];

        $choosableGamePlaces = $this->sortGamePlaces($amountNrCounterMap, $togetherNrCounterMap, $unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($amountNrCounterMap, $togetherNrCounterMap, $remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($togetherNrCounterMap, $newRemainingGamePlaces, $choosableGamePlaces);
            if ($bestGamePlace === null) {
                break;
            }
            $idx = array_search($bestGamePlace, $choosableGamePlaces, true);
            if ($idx !== false) {
                array_splice($choosableGamePlaces, $idx, 1);
            }
            $newRemainingGamePlaces[] = $bestGamePlace;
            if (count($newRemainingGamePlaces) === $variantWithNrOfPlaces->getSportVariant()->getNrOfGamePlaces()) {
                new GameRoundTogetherGame($gameRound, $newRemainingGamePlaces);
                $newRemainingGamePlaces = [];
            }
        }
        if ($finalGameRound && count($newRemainingGamePlaces) > 0) {
            new GameRoundTogetherGame($gameRound, $newRemainingGamePlaces);
        }
        return $newRemainingGamePlaces;
    }

    /**
     * @param AmountNrCounterMap $amountNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param list<GameRoundTogetherGamePlace> $gamePlaces
     * @return list<GameRoundTogetherGamePlace>
     */
    protected function sortGamePlaces(
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (GameRoundTogetherGamePlace $gamePlaceA, GameRoundTogetherGamePlace $gamePlaceB) use ($amountNrCounterMap, $togetherNrCounterMap, $gamePlaces): int {
                $nrOfAssignedGamesA = $amountNrCounterMap->count($gamePlaceA->getPlaceNr());
                $nrOfAssignedGamesB = $amountNrCounterMap->count($gamePlaceB->getPlaceNr());
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($togetherNrCounterMap, $gamePlaceA->getPlaceNr(), $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($togetherNrCounterMap, $gamePlaceB->getPlaceNr(), $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param TogetherCounterMap $togetherCounterMap
     * @param list<GamePlace> $gamePlaces
     * @param list<GamePlace> $choosableGamePlaces
     * @return GamePlace|null
     */
    protected function getBestGamePlace(
        TogetherCounterMap $togetherCounterMap,
        array $gamePlaces,
        array $choosableGamePlaces
    ): GamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($togetherCounterMap, $choosableGamePlace->getPlace(), $gamePlaces);
            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $bestGamePlace = $choosableGamePlace;
            }
        }
        return $bestGamePlace;
    }

//    /**
//     * @param Place $place
//     * @param list<Place> $gamePlaces
//     * @param list<Place> $allPlaces
//     * @return list<Place>
//     */
//    protected function getPlacesToCompare(Place $place, array $gamePlaces, array $allPlaces): array
//    {
//        if (count($gamePlaces) === 0) {
//            return $this->getOtherGamePlaces($place, $allPlaces);
//        }
//        return $gamePlaces;
//    }

    /**
     * @param GameRoundTogetherGamePlace $gamePlace
     * @param list<GameRoundTogetherGamePlace> $gamePlaces
     * @return list<GameRoundTogetherGamePlace>
     */
    protected function getOtherGamePlaces(GameRoundTogetherGamePlace $gamePlace, array $gamePlaces): array
    {
        $idx = array_search($gamePlace, $gamePlaces, true);
        if ($idx === false) {
            return $gamePlaces;
        }
        array_splice($gamePlaces, $idx, 1);
        return $gamePlaces;
    }

    /**
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param int $placeNr
     * @param list<GameRoundTogetherGamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(TogetherNrCounterMap $togetherNrCounterMap, int $placeNr, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($placeNr === $gamePlace->getPlaceNr()) {
                return 100000;
            }
            $duoPlaceNr = new DuoPlaceNr($placeNr, $gamePlace->getPlaceNr());
            $score += $togetherNrCounterMap->count($duoPlaceNr);
        }
        return $score;
    }
}