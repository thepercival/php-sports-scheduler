<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Schedules\GameRounds\ScheduleTogetherGameRound;
use SportsPlanning\Schedules\GameRounds\ScheduleTogetherGameRoundGame;
use SportsPlanning\Schedules\GameRounds\ScheduleTogetherGameRoundGamePlace;

final class ScheduleSingleGameRoundCreator
{
    // protected GameRoundOutput $gameRoundOutput;
//    /**
//     * @var array<string,array<string,PlaceCounter>>
//     */
//    protected array $assignedTogetherMap = [];

    public function __construct(LoggerInterface $logger)
    {
       //  $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        int $nrOfPlaces,
        SingleSportVariant $sportVariant,
        AssignedCounter $singleAssignedCounter
    ): ScheduleTogetherGameRound {
        $variantWithPoule = new SingleWithPoule($nrOfPlaces, $sportVariant);
        $gameRound = new ScheduleTogetherGameRound();
        $placeNrs = range(1, $nrOfPlaces);
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithPoule->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(int $placeNr) => new ScheduleTogetherGameRoundGamePlace($gameRoundNumber, $placeNr), $placeNrs);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithPoule,
                $singleAssignedCounter,
                $gamePlaces,
                $remainingGamePlaces,
                $gameRound
            );
            $singleAssignedCounter->assignTogether($gameRound->toPlaceNrCombinations(), true);
            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithPoule, $singleAssignedCounter, $remainingGamePlaces, [], $gameRound, true);
            $singleAssignedCounter->assignTogether($gameRound->toPlaceNrCombinations(), true);

        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }



    /**
     * @param SingleWithPoule $variantWithPoule
     * @param AssignedCounter $singleAssignedCounter
     * @param list<ScheduleTogetherGameRoundGamePlace> $unSortedGamePlaces
     * @param list<ScheduleTogetherGameRoundGamePlace> $remainingGamePlaces
     * @param ScheduleTogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<ScheduleTogetherGameRoundGamePlace>
     */
    protected function assignGameRound(
        SingleWithPoule $variantWithPoule,
        AssignedCounter $singleAssignedCounter,
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        ScheduleTogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];

        $choosableGamePlaces = $this->sortGamePlaces($singleAssignedCounter, $unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($singleAssignedCounter, $remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($singleAssignedCounter, $newRemainingGamePlaces, $choosableGamePlaces);
            if ($bestGamePlace === null) {
                break;
            }
            $idx = array_search($bestGamePlace, $choosableGamePlaces, true);
            if ($idx !== false) {
                array_splice($choosableGamePlaces, $idx, 1);
            }
            $newRemainingGamePlaces[] = $bestGamePlace;
            if (count($newRemainingGamePlaces) === $variantWithPoule->getSportVariant()->getNrOfGamePlaces()) {
                new ScheduleTogetherGameRoundGame($gameRound, $newRemainingGamePlaces);
                $newRemainingGamePlaces = [];
            }
        }
        if ($finalGameRound && count($newRemainingGamePlaces) > 0) {
            new ScheduleTogetherGameRoundGame($gameRound, $newRemainingGamePlaces);
        }
        return $newRemainingGamePlaces;
    }

    /**
     * @param AssignedCounter $singleAssignedCounter
     * @param list<ScheduleTogetherGameRoundGamePlace> $gamePlaces
     * @return list<ScheduleTogetherGameRoundGamePlace>
     */
    protected function sortGamePlaces(AssignedCounter $singleAssignedCounter,array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (ScheduleTogetherGameRoundGamePlace $gamePlaceA, ScheduleTogetherGameRoundGamePlace $gamePlaceB) use ($singleAssignedCounter, $gamePlaces): int {
                $nrOfAssignedGamesA = $singleAssignedCounter->getAssignedMap()->count($gamePlaceA->getPlaceNr());
                $nrOfAssignedGamesB = $singleAssignedCounter->getAssignedMap()->count($gamePlaceB->getPlaceNr());
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($singleAssignedCounter, $gamePlaceA->getPlaceNr(), $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($singleAssignedCounter, $gamePlaceB->getPlaceNr(), $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param AssignedCounter $singleAssignedCounter
     * @param list<ScheduleTogetherGameRoundGamePlace> $gamePlaces
     * @param list<ScheduleTogetherGameRoundGamePlace> $choosableGamePlaces
     * @return ScheduleTogetherGameRoundGamePlace|null
     */
    protected function getBestGamePlace(
        AssignedCounter $singleAssignedCounter,
        array $gamePlaces,
        array $choosableGamePlaces
    ): ScheduleTogetherGameRoundGamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($singleAssignedCounter, $choosableGamePlace->getPlaceNr(), $gamePlaces);
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
     * @param ScheduleTogetherGameRoundGamePlace $gameRoundGamePlace
     * @param list<ScheduleTogetherGameRoundGamePlace> $gameRoundGamePlaces
     * @return list<ScheduleTogetherGameRoundGamePlace>
     */
    protected function getOtherGamePlaces(ScheduleTogetherGameRoundGamePlace $gameRoundGamePlace, array $gameRoundGamePlaces): array
    {
        $idx = array_search($gameRoundGamePlace, $gameRoundGamePlaces, true);
        if ($idx === false) {
            return $gameRoundGamePlaces;
        }
        array_splice($gameRoundGamePlaces, $idx, 1);
        return $gameRoundGamePlaces;
    }

    /**
     * @param AssignedCounter $singleAssignedCounter
     * @param int $placeNr
     * @param list<ScheduleTogetherGameRoundGamePlace> $gameRoundGamePlaces
     * @return int
     */
    protected function getScore(AssignedCounter $singleAssignedCounter, int $placeNr, array $gameRoundGamePlaces): int
    {
        $score = 0;
        foreach ($gameRoundGamePlaces as $gameRoundGamePlace) {
            if ($placeNr === $gameRoundGamePlace->getPlaceNr()) {
                return 100000;
            }
            $placeNrCounter = $singleAssignedCounter->getTogetherPlaceNrCounter($placeNr, $gameRoundGamePlace->getPlaceNr());
            $score += $placeNrCounter !== null ? $placeNrCounter->count() : 0;
        }
        return $score;
    }


}
