<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\SportVariants\AgainstGpp;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\Schedule\ScheduleGame;
use SportsPlanning\Schedule\ScheduleGamePlace;
use SportsPlanning\Schedule\ScheduleSport;
use SportsScheduler\Combinations\HomeAwayGenerators\GppHomeAwayGenerator;
use SportsScheduler\GameRoundCreators\AgainstGppGameRoundCreator;
use SportsScheduler\Schedule\SportScheduleCreators\Helpers\AgainstDifferenceManager;

class AgainstGppScheduleCreator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function createGamesForSports(
        Schedule                 $schedule,
//        array                    $scheduleSports,
//        SideNrCounterMap         $homeNrCounterMap,
        TogetherNrCounterMap     $togetherNrCounterMap // ,
//        AgainstDifferenceManager $againstGppDifferenceManager,
//        int|null                 $nrOfSecondsBeforeTimeout
    ): void
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $homeAwayGenerator = new GppHomeAwayGenerator($nrOfPlaces);
        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $allScheduleMaps->setTogetherCounterMap($togetherNrCounterMap);
        $allScheduleMaps = clone $allScheduleMaps;

        foreach ($schedule->getSportSchedules() as $scheduleSport) {
            $sportNr = $scheduleSport->getNumber();
            $againstGpp = $scheduleSport->createVariant();
            if( !($againstGpp instanceof AgainstGpp ) ) {
                continue;
            }
            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
//            $gameRound = $gameRoundCreator->createRootAndDescendants(
//                new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp),
//                $homeAwayGenerator,
//                $allScheduleMaps/*,
//                $againstGppDifferenceManager->getAmountRange($sportNr),
//                $againstGppDifferenceManager->getAgainstRange($sportNr),
//                $againstGppDifferenceManager->getWithRange($sportNr),
//                $againstGppDifferenceManager->getHomeRange($sportNr),
//                $nrOfSecondsBeforeTimeout*/
//            );
//
//            $this->createGames($scheduleSport, $gameRound);
//            $allScheduleMaps->addHomeAways($gameRound->getAllHomeAways());
//            $allScheduleMaps = clone $allScheduleMaps;
        }
    }

//    /**
//     * @param Schedule $schedule
//     * @param Single $againstGppsWithNr
//     * @return list<Single|AllInOneGame|AgainstGpp|AgainstH2h>
//     */
//    private function getAllSportVariants(Schedule $schedule, array $againstGppsWithNr) {
//        $sportVariants = $schedule->createSportVariants();
//        $againstGppVariants = array_map( function(SportVariantWithNr $againstGppWithNr): AllInOneGame|Single|AgainstH2h|AgainstGpp {
//            return $againstGppWithNr->sportVariant;
//        }, $againstGppsWithNr );
//        return array_merge($sportVariants, $againstGppVariants);
//    }

    protected function createGames(ScheduleSport $sportSchedule, AgainstGameRound $againstGameRound): void
    {
        while ($againstGameRound !== null) {
            foreach ($againstGameRound->getHomeAways() as $homeAway) {
                $game = new ScheduleGame($sportSchedule);
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach ($homeAway->convertToPlaceNrs($side) as $placeNr) {
                        $gamePlace = new ScheduleGamePlace($game, $placeNr);
                        $gamePlace->setAgainstSide($side);
                        $gamePlace->setGameRoundNumber($againstGameRound->getNumber());
                    }
                }
            }
            $againstGameRound = $againstGameRound->getNext();
        }
    }
}
