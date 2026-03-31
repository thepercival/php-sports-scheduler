<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameVariant;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2hVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGppVariant;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Place;
use SportsPlanning\Schedules\GameRounds\ScheduleTogetherGameRound;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleGame;
use SportsPlanning\Schedules\ScheduleGamePlace;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Permutations\Generators\CombinationsGenerator;
use SportsScheduler\Schedule\GameRoundCreators\ScheduleSingleGameRoundCreator;
use SportsScheduler\Schedule\SportVariantWithNr;

final class ScheduleSingleGameCreatorHelper
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param int $nrOfPlaces
     * @param list<SportVariantWithNr> $singlesWithNr
     * @param AssignedCounter $assignedCounter
     */
    public function createSportSchedules(
        Schedule $schedule,
        int $nrOfPlaces,
        array $singlesWithNr,
        AssignedCounter $assignedCounter): void
    {
        
        $sportVariants = array_map(function(SportVariantWithNr $sportVariantWithNr): SingleSportVariant|AgainstH2hVariant|AgainstGppVariant|AllInOneGameVariant{
            return $sportVariantWithNr->sportVariant;
        }, $singlesWithNr );

        /** @psalm-suppress ArgumentTypeCoercion */
        $singleAssignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
        foreach ($singlesWithNr as $singleWithNr) {
            $sportVariant = $singleWithNr->sportVariant;
            if( !($sportVariant instanceof SingleSportVariant ) ) {
                continue;
            }
            $sportSchedule = new ScheduleSport($schedule, $singleWithNr->number, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($nrOfPlaces, $sportVariant, $singleAssignedCounter);
            $this->createGames($sportSchedule, $gameRound);
        }
        $assignedCounter->setAssignedTogetherMap( $singleAssignedCounter->getAssignedTogetherMap() );
    }

    protected function generateGameRounds(
        int $nrOfPlaces,
        SingleSportVariant $sportVariant,
        AssignedCounter $singleAssignedCounter
    ): ScheduleTogetherGameRound {

        $gameRoundCreator = new ScheduleSingleGameRoundCreator($this->logger);
        $gameRound = $gameRoundCreator->createGameRound($nrOfPlaces, $sportVariant, $singleAssignedCounter);
        // $gameRound = $this->getGameRound($poule, $sportVariant, $assignedCounter, $totalNrOfGamesPerPlace);
        return $gameRound;
    }

    /**
     * @param CombinationsGenerator $combinationsGenerator
     * @return list<PlaceNrCombination>
     */
    protected function toPlaceNrCombinations(CombinationsGenerator $combinationsGenerator): array
    {
        /** @var array<int, list<int>> $combinationsTmp */
        $combinationsTmp = $combinationsGenerator->rewindAndExport();
        return array_values(array_map(
            function (array $placeNrs): PlaceNrCombination {
                return new PlaceNrCombination($placeNrs);
            },
            $combinationsTmp
        ));
    }

//    /**
//     * @param TogetherGameRound $gameRound
//     * @return list<PlaceNrCombination>
//     */
//    protected function gameRoundsToPlaceNrCombinations(TogetherGameRound $gameRound): array
//    {
//        $placeCombinations = $gameRound->getPlaceNrCombinations();
//        while ($gameRound = $gameRound->getNext()) {
//            foreach ($gameRound->getPlaceNrCombinations() as $placeCombination) {
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
//            foreach ($gameRound->getPlaceNrCombinations() as $placeCombination) {
//                $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
//                foreach ($placeCombination->getPlaces() as $place) {
//                    $placeCounter = $placeCounterMap[$place->getNumber()];
//                    new TogetherGamePlace($game, $place, $placeCounter->increment());
//                }
//            }
//            $gameRound = $gameRound->getNext();
//        }
//    }

    protected function createGames(ScheduleSport $sportSchedule, ScheduleTogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new ScheduleGame($sportSchedule, $gameRound->getNumber());
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $gamePlace = new ScheduleGamePlace($game, $gameRoundGamePlace->getPlaceNr());
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
