<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\CombinationMapper;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Maps\Schedule\AmountCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherCounterMap;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\GameRound\Creator\Single as SingleGameRoundCreator;
use SportsScheduler\Schedule\SportVariantWithNr;

class Single
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<SportVariantWithNr> $singlesWithNr
     * @return TogetherCounterMap
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $singlesWithNr): TogetherCounterMap
    {
        $placeCounterMap = (new CombinationMapper())->initPlaceCounterMap($poule);
        $amountCounterMap = new AmountCounterMap($placeCounterMap);
        $togetherCounterMap = new TogetherCounterMap($poule);
        foreach ($singlesWithNr as $singleWithNr) {
            $sportVariant = $singleWithNr->sportVariant;
            if( !($sportVariant instanceof SingleSportVariant ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $singleWithNr->number, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($poule, $sportVariant, $amountCounterMap, $togetherCounterMap);
            $this->createGames($sportSchedule, $gameRound);
        }
        return $togetherCounterMap;
    }

    protected function generateGameRounds(
        Poule $poule,
        SingleSportVariant $sportVariant,
        AmountCounterMap $amountCounterMap,
        TogetherCounterMap $togetherCounterMap
    ): TogetherGameRound {

        $gameRoundCreator = new SingleGameRoundCreator($this->logger);
        $gameRound = $gameRoundCreator->createGameRound($poule, $sportVariant, $amountCounterMap, $togetherCounterMap);

        // $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $totalNrOfGamesPerPlace);
        return $gameRound;
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return list<PlaceCombination>
     */
    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
    {
        /** @var array<int, list<Place>> $combinationsTmp */
        $combinationsTmp = $combinations->toArray();
        return array_values(array_map(
            function (array $places): PlaceCombination {
                return new PlaceCombination($places);
            },
            $combinationsTmp
        ));
    }

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
                    $gamePlace = new GamePlace($game, $gameRoundGamePlace->getPlace()->getPlaceNr());
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
