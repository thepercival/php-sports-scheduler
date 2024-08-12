<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\CombinationMapper;
use SportsPlanning\Counters\Maps\Schedule\HomeCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherCounterMap;
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
     * @param TogetherCounterMap $togetherCounterMap
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @return SideCounterMap
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule $schedule,
        array $againstH2hsWithNr,
        AgainstDifferenceManager $againstGppDifferenceManager
    ): SideCounterMap
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $placeCounterMap = (new CombinationMapper())->initPlaceCounterMap($poule);
        $homeCounterMap = new SideCounterMap(Side::Home, $placeCounterMap);
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
                $poule,
                $sportVariant,
                $homeAwayCreator,
                $homeCounterMap,
                $againstGppDifferenceManager->getHomeRange($sportNr)
            );

            $this->createGames($sportSchedule, $gameRound);
        }
        return $homeCounterMap;
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }



}
