<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;


use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameBase;
use SportsPlanning\Schedules\GameRounds\ScheduleTogetherGameRound;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleGame;
use SportsPlanning\Schedules\ScheduleGamePlace;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Schedule\SportVariantWithNr;

final class ScheduleAllInOneGameCreatorHelper
{
    public function __construct()
    {
    }

    /**
     * @param Schedule $schedule
     * @param int $nrOfPlaces
     * @param list<SportVariantWithNr> $allInOneGamesWithNr
     */
    public function createScheduleSports(
        Schedule $schedule,
        int $nrOfPlaces,
        array $allInOneGamesWithNr): void
    {
        foreach ($allInOneGamesWithNr as $allInOneGameWithNr) {
            $sportVariant = $allInOneGameWithNr->sportVariant;
            if( !($sportVariant instanceof AllInOneGameBase ) ) {
                continue;
            }
            $scheduleSport = new ScheduleSport($schedule, $allInOneGameWithNr->number, $sportVariant->toPersistVariant());
            $nrOfGamesPerPlace = $sportVariant->getNrOfGamesPerPlace();
            $this->createGames($scheduleSport, $nrOfPlaces, $nrOfGamesPerPlace);
        }
    }

    protected function createGames(
        ScheduleSport $scheduleSport,
        int $nrOfPlaces,
        int $nrOfGamesPerPlace): ScheduleTogetherGameRound
    {
        /** @var ScheduleTogetherGameRound|null $previous */
        $previous = null;
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $nrOfGamesPerPlace ; $gameRoundNumber++) {
            $gameRound = $previous === null ? new ScheduleTogetherGameRound() : $previous->createNext();

            $scheduleGame = new ScheduleGame($scheduleSport, $gameRoundNumber);

            for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
                $scheduleGamePlace = new ScheduleGamePlace($scheduleGame, $placeNr);
                $scheduleGamePlace->setGameRoundNumber($gameRoundNumber);
            }

            $previous = $gameRound;
        }
        if (!isset($gameRound)) {
            throw new \Exception('no gamerounds created', E_ERROR);
        }
        /** @var ScheduleTogetherGameRound $gameRound */
        return $gameRound->getFirst();
    }
}
