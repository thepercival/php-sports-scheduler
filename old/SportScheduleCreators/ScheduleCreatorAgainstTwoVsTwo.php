<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\SportScheduleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\GameRounds\AgainstGameRound;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;
use SportsScheduler\Combinations\HomeAwayGenerators\GppHomeAwayGenerator;
use SportsScheduler\Schedule\CycleCreators\CycleCreatorTwoVsTwoAgainst;

class ScheduleCreatorAgainstTwoVsTwo
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function createGamesForSports(
        ScheduleWithNrOfPlaces $schedule,
//        array                    $scheduleSports,
//        SideNrCounterMap         $homeNrCounterMap,
        TogetherNrCounterMap     $togetherNrCounterMap // ,
//        AgainstDifferenceManager $againstGppDifferenceManager,
//        int|null                 $nrOfSecondsBeforeTimeout
    ): void
    {
        $nrOfPlaces = $schedule->nrOfPlaces;
        $homeAwayGenerator = new GppHomeAwayGenerator($nrOfPlaces);
        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $allScheduleMaps->setTogetherCounterMap($togetherNrCounterMap);
        $allScheduleMaps = clone $allScheduleMaps;

        foreach ($schedule->getAgainstSportSchedules() as $sportSchedule) {
            if( !($sportSchedule instanceof ScheduleAgainstTwoVsTwo ) ) {
                continue;
            }
            $gameRoundCreator = new CycleCreatorTwoVsTwoAgainst($this->logger);

            $againstGameRound = $gameRoundCreator->createRootAndDescendants(
                $nrOfPlaces, $sportSchedule
            );

            $this->addGamesToSportSchedule($sportSchedule, $againstGameRound);

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


    protected function addGamesToSportSchedule(
        ScheduleAgainstTwoVsTwo $sportSchedule, AgainstGameRound $againstGameRound): void
    {
        while ($againstGameRound !== null) {
            foreach ($againstGameRound->getHomeAways() as $homeAway) {

                $game = new ScheduleGameAgainstTwoVsTwo(
                    $sportSchedule,
                    $againstGameRound->getNumber(), 0
                );
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach ($homeAway->convertToPlaceNrs($side) as $placeNr) {
                        new ScheduleGamePlaceAgainst($game, $side, $placeNr);
                    }
                }
            }
            $againstGameRound = $againstGameRound->getNext();
        }
    }
}
