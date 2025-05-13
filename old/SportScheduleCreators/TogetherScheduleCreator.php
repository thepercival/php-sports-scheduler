<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsScheduler\Schedule\CycleCreators\CycleCreatorTogether;

class TogetherScheduleCreator
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param ScheduleWithNrOfPlaces $schedule
     * @return TogetherNrCounterMap
     */
    public function createRootCycleForSport(
        ScheduleTogetherSport $sportSchedule,
        TogetherNrCounterMap $togetherNrCounterMap): TogetherNrCounterMap
    {
        $nrOfPlaces = $schedule->nrOfPlaces;
        $amountNrCounterMap = new AmountNrCounterMap($nrOfPlaces);

        foreach ($schedule->getTogetherSportSchedules() as $togetherSportSchedule) {

            $togetherSport = $togetherSportSchedule->sport;
            if( $togetherSport->getNrOfGamePlaces() !== null ) {
                $gameRound = $this->generateGameRounds($nrOfPlaces, $togetherSport, $amountNrCounterMap, $togetherNrCounterMap);
            } else {
                $gameRound = $this->generateAllInOneGameGameRounds($nrOfPlaces);
            }
            $this->addGamesToSportSchedule($togetherSportSchedule, $gameRound);
        }
        return $togetherNrCounterMap;
    }

    protected function generateGameRounds(
        int $nrOfPlaces,
        TogetherSport $togetherSport,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap
    ): TogetherGameRound {

        $cycleCreator = new CycleCreatorTogether($this->logger);
        return $cycleCreator->createRootCycle($nrOfPlaces, $togetherSport, $amountNrCounterMap, $togetherNrCounterMap);
    }

    protected function generateAllInOneGameGameRounds(int $nrOfPlaces): TogetherGameRound
    {
        $gameRound = null;
        /** @var TogetherGameRound|null $previous */
        $previous = null;
        for ($cycleNr = 1 ; $cycleNr <= $nrOfPlaces ; $cycleNr++) {
            $gameRound = $previous === null ? new TogetherGameRound($nrOfPlaces) : $previous->createNext();

            $gamePlaces = array_map(function(int $placeNr) use($cycleNr) : TogetherGameRoundGamePlace {
                return new TogetherGameRoundGamePlace($cycleNr, $placeNr);
            }, (new SportRange(1, $nrOfPlaces))->toArray() );

            $gameRound->addGame($gamePlaces);

            $previous = $gameRound;
        }
        if ( $gameRound === null ) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }

        return $gameRound->getFirst();
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

    protected function addGamesToSportSchedule(
        ScheduleTogetherSport $sportSchedule,TogetherGameRound $togetherGameRound): void
    {
        while ($togetherGameRound !== null) {
            foreach ($togetherGameRound->getGames() as $togetherGameRoundGame) {
                $game = new ScheduleGameTogether($sportSchedule);
                foreach ($togetherGameRoundGame->gamePlaces as $togetherGameRoundGamePlace) {
                    new ScheduleGamePlaceTogether(
                        $game,
                        $togetherGameRoundGamePlace->placeNr,
                        $togetherGameRoundGamePlace->gameRoundNumber
                    );
                }
            }
            $togetherGameRound = $togetherGameRound->getNext();
        }
    }
}