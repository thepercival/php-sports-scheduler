<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Schedules\GameRounds\ScheduleAgainstGameRound;
use SportsPlanning\Schedules\ScheduleGame;
use SportsPlanning\Schedules\ScheduleGamePlace;
use SportsPlanning\Schedules\ScheduleSport;

abstract class ScheduleAgainstCreatorHelperAbstract
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    protected function createGames(ScheduleSport $scheduleSport, ScheduleAgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new ScheduleGame($scheduleSport, $gameRound->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach ($homeAway->get($side)->getPlaceNrs() as $placeNr) {
                        $gamePlace = new ScheduleGamePlace($game, $placeNr);
                        $gamePlace->setAgainstSide($side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
