<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\Combinations\HomeAwayGenerators\H2hHomeAwayGenerator;
use SportsScheduler\GameRoundCreators\AgainstH2hGameRoundCreator;
use SportsScheduler\Schedule\SportScheduleCreators\Helpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\SportVariantWithNr;

class AgainstH2hScheduleCreator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @return SideNrCounterMap
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        array $sportVariantsWithNr,
        AgainstDifferenceManager $againstGppDifferenceManager
    ): SideNrCounterMap
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $homeNrCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
        $homeAwayCreator = new H2hHomeAwayGenerator();
        $sportNr = 1;
        foreach ($sportVariantsWithNr as $sportVariantWithNr) {
            $againstH2h = $sportVariantWithNr->sportVariant;
            if( !($againstH2h instanceof AgainstH2h ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $sportNr, $againstH2h->toPersistVariant());

            $gameRoundCreator = new AgainstH2hGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $nrOfPlaces,
                $againstH2h,
                $homeAwayCreator,
                $homeNrCounterMap,
                $againstGppDifferenceManager->getHomeRange($sportNr)
            );

            $this->createGames($sportSchedule, $gameRound);
        }
        return $homeNrCounterMap;
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }

    protected function createGames(SportSchedule $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {

                if( $homeAway instanceof OneVsOneHomeAway ) {
                    $game = new Game($sportSchedule, $gameRound->getNumber());
                    foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                        $gamePlace = new GamePlace($game, $homeAway->get($side));
                        $gamePlace->setAgainstSide($side);
                    }
                } else {
                    throw new \Exception('h2h can only be 1vs1');
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }

}
