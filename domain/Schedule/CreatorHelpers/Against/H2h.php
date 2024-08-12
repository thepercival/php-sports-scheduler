<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsScheduler\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsScheduler\GameRound\Creator\Against\H2h as AgainstH2hGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\Schedule\CreatorHelpers\Against as AgainstHelper;
use SportsScheduler\Schedule\SportVariantWithNr;

class H2h extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $againstH2hsWithNr
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @return SideNrCounterMap
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        array $againstH2hsWithNr,
        AgainstDifferenceManager $againstGppDifferenceManager
    ): SideNrCounterMap
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $homeNrCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
        $homeAwayCreator = new H2hHomeAwayCreator();
        $sportNr = 1;
        foreach ($againstH2hsWithNr as $againstH2hWithNr) {
            $sportVariant = $againstH2hWithNr->sportVariant;
            if( !($sportVariant instanceof AgainstH2h ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new AgainstH2hGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $nrOfPlaces,
                $sportVariant,
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



}
