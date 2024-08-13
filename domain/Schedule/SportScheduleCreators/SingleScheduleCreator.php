<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\GameRoundCreators\SingleGameRoundCreator;
use SportsScheduler\Schedule\SportVariantWithNr;

class SingleScheduleCreator
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $singlesWithNr
     * @return TogetherNrCounterMap
     */
    public function createSportSchedules(
        Schedule $schedule,
        array $singlesWithNr): TogetherNrCounterMap
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $amountNrCounterMap = new AmountNrCounterMap($nrOfPlaces);
        $togetherNrCounterMap = new TogetherNrCounterMap($nrOfPlaces);
        foreach ($singlesWithNr as $singleWithNr) {
            $sportVariant = $singleWithNr->sportVariant;
            if( !($sportVariant instanceof SingleSportVariant ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $singleWithNr->number, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($nrOfPlaces, $sportVariant, $amountNrCounterMap, $togetherNrCounterMap);
            $this->createGames($sportSchedule, $gameRound);
        }
        return $togetherNrCounterMap;
    }

    protected function generateGameRounds(
        int $nrOfPlaces,
        SingleSportVariant $sportVariant,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap
    ): TogetherGameRound {

        $gameRoundCreator = new SingleGameRoundCreator($this->logger);
        $gameRound = $gameRoundCreator->createGameRound($nrOfPlaces, $sportVariant, $amountNrCounterMap, $togetherNrCounterMap);

        // $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $totalNrOfGamesPerPlace);
        return $gameRound;
    }

//    /**
//     * @param CombinationsGenerator $combinations
//     * @return list<PlaceCombination>
//     */
//    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
//    {
//        /** @var array<int, list<Place>> $combinationsTmp */
//        $combinationsTmp = $combinations->toArray();
//        return array_values(array_map(
//            function (array $places): PlaceCombination {
//                return new PlaceCombination($places);
//            },
//            $combinationsTmp
//        ));
//    }

//    /**
//     * @param TogetherGameRound $gameRound
//     * @return list<PlaceCombination>
//     */
//    protected function gameRoundsToPlaceCombinations(TogetherGameRound $gameRound): array
//    {
//        $placeCombinations = $gameRound->getPlaceCombinations();
//        while ($gameRound = $gameRound->getNext()) {
//            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
//                array_push($placeCombinations, $placeCombination);
//            }
//        }
//        return $placeCombinations;
//    }

//    /**
//     * @param Poule $poule
//     * @return array<string|int, PlaceCounter>
//     */
//    protected function getPlaceCounterMap(Poule $poule): array
//    {
//        $placeCounterMap = [];
//        foreach ($poule->getPlaces() as $place) {
//            $placeCounterMap[$place->getNumber()] = new PlaceCounter($place);
//        }
//        return $placeCounterMap;
//    }

//    protected function getDefaultField(): Field
//    {
//        if ($this->defaultField === null) {
//            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
//        }
//        return $this->defaultField;
//    }

//    /**
//     * @param Poule $poule
//     * @param SingleGameRound $gameRound
//     * @throws Exception
//     */
//    protected function gameRoundsToGames(Poule $poule, SingleGameRound $gameRound): void
//    {
//        $placeCounterMap = $this->getPlaceCounterMap($poule);
//        while ($gameRound !== null) {
//            foreach ($gameRound->getPlaceCombinations() as $placeCombination) {
//                $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
//                foreach ($placeCombination->getPlaces() as $place) {
//                    $placeCounter = $placeCounterMap[$place->getNumber()];
//                    new TogetherGamePlace($game, $place, $placeCounter->increment());
//                }
//            }
//            $gameRound = $gameRound->getNext();
//        }
//    }

    protected function createGames(SportSchedule $sportSchedule, TogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new Game($sportSchedule);
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $gamePlace = new GamePlace($game, $gameRoundGamePlace->getPlaceNr());
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
