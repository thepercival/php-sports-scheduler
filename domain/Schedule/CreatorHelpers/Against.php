<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\Schedule\Sport as SportSchedule;

abstract class Against
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    protected function createGames(SportSchedule $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new Game($sportSchedule, $gameRound->getNumber());
                if( $homeAway instanceof OneVsOneHomeAway ) {
                    foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                        $gamePlace = new GamePlace($game, $homeAway->get($side));
                        $gamePlace->setAgainstSide($side);
                    }
                } else if( $homeAway instanceof OneVsTwoHomeAway ) {
                    $gamePlace = new GamePlace($game, $homeAway->getHome());
                    $gamePlace->setAgainstSide(AgainstSide::Home);
                    foreach ($homeAway->getAway()->getPlaceNrs() as $placeNr) {
                        $gamePlace = new GamePlace($game, $placeNr);
                        $gamePlace->setAgainstSide(AgainstSide::Away);
                    }
                } else { // TwoVsTwoHomeAway
                    foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                        foreach ($homeAway->get($side)->getPlaceNrs() as $placeNr) {
                            $gamePlace = new GamePlace($game, $placeNr);
                            $gamePlace->setAgainstSide($side);
                        }
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
