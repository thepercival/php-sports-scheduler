<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\GameRounds\AgainstGameRound;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsScheduler\Schedule\CycleCreators\CycleCreatorAgainstOneVsOne;

class ScheduleCreatorAgainstOneVsOne
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function createGamesForSport(ScheduleAgainstOneVsOne $sportSchedule): void
    {
        $nrOfPlaces = $sportSchedule->getSchedule()->getNrOfPlaces();

        $gameRoundCreator = new CycleCreatorAgainstOneVsOne($this->logger);
        $firstGameRound = $gameRoundCreator->createRootAndDescendants(
            $nrOfPlaces,
            $sportSchedule->sport
        );

        $this->createGames($scheduleSport, $firstGameRound);
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }

    protected function addGamesToSportSchedule(
        ScheduleAgainstOneVsOne $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new ScheduleGameAgainstOneVsOne($sportSchedule, 1, $gameRound->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach( $homeAway->convertToPlaceNrs($side) as $sidePlaceNr ) {
                        new ScheduleGamePlaceAgainst($game, $side, $sidePlaceNr);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }

}
