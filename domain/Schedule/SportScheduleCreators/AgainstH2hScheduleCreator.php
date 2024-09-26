<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\Schedule\ScheduleGame;
use SportsPlanning\Schedule\ScheduleGamePlace;
use SportsPlanning\Schedule\ScheduleSport;
use SportsScheduler\GameRoundCreators\AgainstH2hGameRoundCreator;

class AgainstH2hScheduleCreator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function createGamesForSport(ScheduleSport $scheduleSport): void
    {
        $nrOfPlaces = $scheduleSport->getSchedule()->getNrOfPlaces();

        $againstH2h = $scheduleSport->createVariant();
        if( !($againstH2h instanceof AgainstH2h ) ) {
            return;
        }

        $gameRoundCreator = new AgainstH2hGameRoundCreator($this->logger);
        $firstGameRound = $gameRoundCreator->createRootAndDescendants(
            $nrOfPlaces,
            $againstH2h
        );

        $this->createGames($scheduleSport, $firstGameRound);
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }

    protected function createGames(ScheduleSport $scheduleSport, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new ScheduleGame($scheduleSport, $gameRound->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach( $homeAway->convertToPlaceNrs($side) as $sidePlaceNr ) {
                        $gamePlace = new ScheduleGamePlace($game, $sidePlaceNr);
                        $gamePlace->setAgainstSide($side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }

}
