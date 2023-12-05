<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Exception;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameBase;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\GameRound\Together\Game as TogetherGame;
use SportsPlanning\GameRound\Together\GamePlace as TogetherGamePlace;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
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
     * @param Poule $poule
     * @param list<SportVariantWithNr> $allInOneGamesWithNr
     */
    public function createSportSchedules(
        Schedule $schedule,
        Poule $poule,
        array $allInOneGamesWithNr): void
    {
        foreach ($allInOneGamesWithNr as $allInOneGameWithNr) {
            $sportVariant = $allInOneGameWithNr->sportVariant;
            if( !($sportVariant instanceof AllInOneGameBase ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $allInOneGameWithNr->number, $sportVariant->toPersistVariant());
            $gameRound = $this->generateGameRounds($poule, $sportVariant);
            $this->createGames($sportSchedule, $gameRound);
        }
    }

    protected function generateGameRounds(Poule $poule, AllInOneGameSportVariant $sportVariant): TogetherGameRound
    {
        /** @var TogetherGameRound|null $previous */
        $previous = null;
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $sportVariant->getNrOfGamesPerPlace() ; $gameRoundNumber++) {
            $gameRound = $previous === null ? new TogetherGameRound() : $previous->createNext();

            $gamePlaces = [];
            foreach ($poule->getPlaces() as $place) {
                $gamePlaces[] = new TogetherGamePlace($gameRoundNumber, $place);
            }
            new TogetherGame($gameRound, $gamePlaces);

            $previous = $gameRound;
        }
        if (!isset($gameRound)) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }
        /** @var TogetherGameRound $gameRound */
        return $gameRound->getFirst();
    }

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
