<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use SportsHelpers\SportRange;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGamePlace;
use SportsPlanning\Schedule\ScheduleGame;
use SportsPlanning\Schedule\ScheduleGamePlace;
use SportsPlanning\Schedule\ScheduleSport;

class AllInOneGameScheduleCreator
{
    public function __construct()
    {
    }

    public function createGamesForSports(Schedule $schedule): void
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        foreach( $schedule->getSportSchedules() as $scheduleSport) {
            $allInOneGameVariant = $scheduleSport->createVariant();
            if( !($allInOneGameVariant instanceof AllInOneGame ) ) {
                return;
            }
            $gameRound = $this->generateGameRounds($nrOfPlaces, $allInOneGameVariant);
            $this->createGamesForGameRounds($scheduleSport, $gameRound);
        }
    }

    protected function generateGameRounds(int $nrOfPlaces, AllInOneGame $allInOneGame): TogetherGameRound
    {
        $gameRound = null;
        /** @var TogetherGameRound|null $previous */
        $previous = null;
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $allInOneGame->nrOfGamesPerPlace ; $gameRoundNumber++) {
            $gameRound = $previous === null ? new TogetherGameRound($nrOfPlaces) : $previous->createNext();

            $gamePlaces = array_map(function(int $placeNr) use($gameRoundNumber) : TogetherGameRoundGamePlace {
                return new TogetherGameRoundGamePlace($gameRoundNumber, $placeNr);
            }, (new SportRange(1, $nrOfPlaces))->toArray() );

            $gameRound->addGame($gamePlaces);

            $previous = $gameRound;
        }
        if ( $gameRound === null ) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }

        return $gameRound->getFirst();
    }

    protected function createGamesForGameRounds(ScheduleSport $schduleSport, TogetherGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getGames() as $gameRoundGame) {
                $game = new ScheduleGame($schduleSport);
                foreach ($gameRoundGame->gamePlaces as $gameRoundGamePlace) {
                    $gamePlace = new ScheduleGamePlace($game, $gameRoundGamePlace->placeNr);
                    $gamePlace->setGameRoundNumber($gameRoundGamePlace->gameRoundNumber);
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
