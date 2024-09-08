<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Single as SingleWithNrOfPlaces;
use SportsHelpers\SportRange;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Output\Combinations\GameRoundOutput;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGame;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGamePlace;

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
        Single $singleVariant,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap
    ): TogetherGameRound {
        $variantWithNrOfPlaces = new SingleWithNrOfPlaces($nrOfPlaces, $singleVariant);
        $gameRound = new TogetherGameRound($nrOfPlaces);
        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithNrOfPlaces->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(int $placeNr) => new TogetherGameRoundGamePlace($gameRoundNumber, $placeNr), $placeNrs);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithNrOfPlaces,
                $amountNrCounterMap,
                $togetherNrCounterMap,
                $gamePlaces,
                $remainingGamePlaces,
                $gameRound
            );
            foreach( $gameRound->getGames() as $gameRoundGame ) {
                foreach( $gameRoundGame->convertToPlaceNrs() as $placeNr ) {
                    $amountNrCounterMap->incrementPlaceNr($placeNr);
                }
                foreach( $gameRoundGame->convertToDuoPlaceNrs() as $duoPlaceNr ) {
                    $togetherNrCounterMap->incrementDuoPlaceNr($duoPlaceNr);
                }
            }
            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithNrOfPlaces, $amountNrCounterMap, $togetherNrCounterMap, $remainingGamePlaces, [], $gameRound, true);
            foreach( $gameRound->getGames() as $gameRoundGame ) {
                foreach( $gameRoundGame->convertToPlaceNrs() as $placeNr ) {
                    $amountNrCounterMap->incrementPlaceNr($placeNr);
                }
                foreach( $gameRoundGame->convertToDuoPlaceNrs() as $duoPlaceNr ) {
                    $togetherNrCounterMap->incrementDuoPlaceNr($duoPlaceNr);
                }
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
     * @param list<TogetherGameRoundGamePlace> $unsortedGamePlaces
     * @param list<TogetherGameRoundGamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<TogetherGameRoundGamePlace>
     */
    protected function assignGameRound(
        SingleWithNrOfPlaces $variantWithNrOfPlaces,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $unsortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];

        $choosableGamePlaces = $this->sortGamePlaces($amountNrCounterMap, $togetherNrCounterMap, $unsortedGamePlaces);
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
                $gameRound->addGame($newRemainingGamePlaces);
                $newRemainingGamePlaces = [];
            }
        }
        if ($finalGameRound && count($newRemainingGamePlaces) > 0) {
            $gameRound->addGame($newRemainingGamePlaces);
        }
        return $newRemainingGamePlaces;
    }

    /**
     * @param AmountNrCounterMap $amountNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     * @return list<TogetherGameRoundGamePlace>
     */
    protected function sortGamePlaces(
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (TogetherGameRoundGamePlace $gamePlaceA, TogetherGameRoundGamePlace $gamePlaceB) use ($amountNrCounterMap, $togetherNrCounterMap, $gamePlaces): int {
                $nrOfAssignedGamesA = $amountNrCounterMap->count($gamePlaceA->placeNr);
                $nrOfAssignedGamesB = $amountNrCounterMap->count($gamePlaceB->placeNr);
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($togetherNrCounterMap, $gamePlaceA->placeNr, $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($togetherNrCounterMap, $gamePlaceB->placeNr, $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param TogetherNrCounterMap $togetherCounterMap
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     * @param list<TogetherGameRoundGamePlace> $choosableGamePlaces
     * @return TogetherGameRoundGamePlace|null
     */
    protected function getBestGamePlace(
        TogetherNrCounterMap $togetherCounterMap,
        array $gamePlaces,
        array $choosableGamePlaces
    ): TogetherGameRoundGamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($togetherCounterMap, $choosableGamePlace->placeNr, $gamePlaces);
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
     * @param TogetherGameRoundGamePlace $gamePlace
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     * @return list<TogetherGameRoundGamePlace>
     */
    protected function getOtherGamePlaces(TogetherGameRoundGamePlace $gamePlace, array $gamePlaces): array
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
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(TogetherNrCounterMap $togetherNrCounterMap, int $placeNr, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($placeNr === $gamePlace->placeNr) {
                return 100000;
            }
            $duoPlaceNr = new DuoPlaceNr($placeNr, $gamePlace->placeNr);
            $score += $togetherNrCounterMap->count($duoPlaceNr);
        }
        return $score;
    }
}
