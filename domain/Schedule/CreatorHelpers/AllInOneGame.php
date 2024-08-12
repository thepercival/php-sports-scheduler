<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Exception;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameBase;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Schedule\Game as ScheduleGame;
use SportsPlanning\Schedule\GamePlace as ScheduleGamePlace;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule\GameRounds\GameRoundTogetherGame;
use SportsPlanning\Schedule\GameRounds\GameRoundTogetherGamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Sport;
use SportsScheduler\Schedule\SportVariantWithNr;

class AllInOneGame
{
    public function __construct()
    {
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $allInOneGamesWithNr
     */
    public function createSportSchedules(
        Schedule $schedule,
        array $allInOneGamesWithNr): void
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        foreach ($allInOneGamesWithNr as $allInOneGameWithNr) {
            $sportVariant = $allInOneGameWithNr->sportVariant;
            if( !($sportVariant instanceof AllInOneGameBase ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $allInOneGameWithNr->number, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($nrOfPlaces, $sportVariant);
            $this->createGames($sportSchedule, $gameRound);
        }
    }

    protected function generateGameRounds(int $nrOfPlaces, AllInOneGameSportVariant $sportVariant): TogetherGameRound
    {
        $gameRound = null;
        /** @var TogetherGameRound|null $previous */
        $previous = null;
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $sportVariant->getNrOfGamesPerPlace() ; $gameRoundNumber++) {
            $gameRound = $previous === null ? new TogetherGameRound() : $previous->createNext();

            $gamePlaces = array_map(function(int $placeNr) use($gameRoundNumber) : GameRoundTogetherGamePlace {
                return new GameRoundTogetherGamePlace($gameRoundNumber, $placeNr);
            }, (new SportRange(1, $nrOfPlaces))->toArray() );

            new GameRoundTogetherGame($gameRound, $gamePlaces);

            $previous = $gameRound;
        }
        if ( $gameRound === null ) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }

        return $gameRound->getFirst();
    }

    protected function createGames(SportSchedule $sportSchedule, TogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new ScheduleGame($sportSchedule);
                foreach ($gameRoundGame->getGamePlaces() as $gameRoundGamePlace) {
                    $gamePlace = new ScheduleGamePlace($game, $gameRoundGamePlace->getPlaceNr());
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->getGameRoundNumber());
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
