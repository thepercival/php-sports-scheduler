<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\SideCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherCounterMap;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsScheduler\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsScheduler\GameRound\Creator\Against\GamesPerPlace as AgainstGppGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\Schedule\CreatorHelpers\Against as AgainstHelper;
use SportsScheduler\Schedule\SportVariantWithNr;

class GamesPerPlace extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $againstGppsWithNr
     * @param SideCounterMap $homeCounterMap
     * @param TogetherCounterMap $togetherCounterMap
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule                 $schedule,
        array                    $againstGppsWithNr,
        SideCounterMap           $homeCounterMap,
        TogetherCounterMap       $togetherCounterMap,
        AgainstDifferenceManager $againstGppDifferenceManager,
        int|null                 $nrOfSecondsBeforeTimeout
    ): void
    {
        $homeAwayCreator = new GppHomeAwayCreator();
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $allScheduleMaps->setHomeCounterMap($homeCounterMap);
        $allScheduleMaps->setTogetherCounterMap($togetherCounterMap);
        $allScheduleMaps = clone $allScheduleMaps;

        foreach ($againstGppsWithNr as $againstGppWithNr) {
            $sportNr = $againstGppWithNr->number;
            $sportVariant = $againstGppWithNr->sportVariant;
            if( !($sportVariant instanceof AgainstGpp ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $poule,
                $sportVariant,
                $homeAwayCreator,
                $allScheduleMaps,
                $againstGppDifferenceManager->getAmountRange($sportNr),
                $againstGppDifferenceManager->getAgainstRange($sportNr),
                $againstGppDifferenceManager->getWithRange($sportNr),
                $againstGppDifferenceManager->getHomeRange($sportNr),
                $nrOfSecondsBeforeTimeout
            );

            $this->createGames($sportSchedule, $gameRound);
            $allScheduleMaps->addHomeAways($gameRound->getAllHomeAways());
            $allScheduleMaps = clone $allScheduleMaps;
        }
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $againstGppsWithNr
     * @return list<AllInOneGame|Single|AgainstH2h|AgainstGpp>
     */
    private function getAllSportVariants(Schedule $schedule, array $againstGppsWithNr) {
        $sportVariants = $schedule->createSportVariants();
        $againstGppVariants = array_map( function(SportVariantWithNr $againstGppWithNr): AllInOneGame|Single|AgainstH2h|AgainstGpp {
            return $againstGppWithNr->sportVariant;
        }, $againstGppsWithNr );
        return array_merge($sportVariants, $againstGppVariants);
    }
}
