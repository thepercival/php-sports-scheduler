<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\Combinations\HomeAwayCreators\GamesPerPlaceHomeAwayCreator as GppHomeAwayCreator;
use SportsScheduler\GameRoundCreators\AgainstGppGameRoundCreator;
use SportsScheduler\Schedule\SportScheduleCreators\Helpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\SportVariantWithNr;

class AgainstGppScheduleCreator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param Schedule $schedule
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param SideNrCounterMap $homeNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule                 $schedule,
        array                    $sportVariantsWithNr,
        SideNrCounterMap         $homeNrCounterMap,
        TogetherNrCounterMap     $togetherNrCounterMap,
        AgainstDifferenceManager $againstGppDifferenceManager,
        int|null                 $nrOfSecondsBeforeTimeout
    ): void
    {
        $homeAwayCreator = new GppHomeAwayCreator();
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $allScheduleMaps->setHomeCounterMap($homeNrCounterMap);
        $allScheduleMaps->setTogetherCounterMap($togetherNrCounterMap);
        $allScheduleMaps = clone $allScheduleMaps;

        foreach ($sportVariantsWithNr as $sportVariantWithNr) {
            $sportNr = $sportVariantWithNr->number;
            $againstGpp = $sportVariantWithNr->sportVariant;
            if( !($againstGpp instanceof AgainstGpp ) ) {
                continue;
            }
            $sportSchedule = new SportSchedule($schedule, $sportNr, $againstGpp->toPersistVariant());

            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $nrOfPlaces,
                $againstGpp,
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
