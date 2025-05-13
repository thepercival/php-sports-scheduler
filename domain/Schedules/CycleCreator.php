<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules;

use Psr\Log\LoggerInterface;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorAgainstOneVsOne;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorAgainstOneVsTwo;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorAgainstTwoVsTwo;
use SportsScheduler\Schedules\CycleCreators\CycleCreatorTogether;

// use SportsPlanning\Counters\AssignedCounter;

class CycleCreator
{
    public const int MaxNrOfSports = 12;
    public const int MaxNrOfGames = 496;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param ScheduleWithNrOfPlaces $scheduleWithNrOfPlace
     * @return list<ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo>
     */
    public function createSportRootCycles(ScheduleWithNrOfPlaces $scheduleWithNrOfPlace): array
    {
        $sportsWithNrOfCycles = array_map( function(
            ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo $sportSchedule
        ): SportWithNrOfCycles {
            return $sportSchedule->createSportWithNrOfCycles();
        }, $scheduleWithNrOfPlace->getSportSchedules() );

        return $this->createScheduleCyclesFromNrOfPlacesAndSportsWithNrOfCycles(
            $scheduleWithNrOfPlace->nrOfPlaces,
            $sportsWithNrOfCycles);
    }

    /**
     * @param int $nrOfPlaces,
     * @param list<SportWithNrOfCycles> $sportsWithNrOfCycles
     * @return list<ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo>
     */
    private function createScheduleCyclesFromNrOfPlacesAndSportsWithNrOfCycles(
        int $nrOfPlaces, array $sportsWithNrOfCycles): array
    {
//        $sports = array_values(
//            array_map( function (SportWithNrOfCycles $sportWithNrOfCycles): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport {
//                return $sportWithNrOfCycles->sport;
//            }, $sportsWithNrOfCycles)
//        );

        $schedule = new ScheduleWithNrOfPlaces($nrOfPlaces, $sportsWithNrOfCycles);

        // Start : convert sportmap to schedulesports
        $rootCycles = [];

        $cycleCreatorTogether = new CycleCreatorTogether($this->logger);
        $togetherNrCounterMap = new TogetherNrCounterMap($nrOfPlaces);
        foreach ($schedule->getTogetherSportSchedules() as $togetherSportSchedule) {
            // wat doe je met de rootCycles?
            // per $sportWithNrOfCycles
            // meerdere rootCycle samenvoegeb
            $rootCycles[] = $cycleCreatorTogether->createRootCycleAndGames($togetherSportSchedule, $togetherNrCounterMap);
        }

        $cycleCreatorAgainstOneVsOne = new CycleCreatorAgainstOneVsOne($this->logger);
        foreach ($schedule->getAgainstOneVsOneSchedules() as $scheduleAgainstOneVsOne) {
            $rootCycles[] = $cycleCreatorAgainstOneVsOne->createRootCycleAndGames($scheduleAgainstOneVsOne);
        }

        $cycleCreatorAgainstOneVsTwo = new CycleCreatorAgainstOneVsTwo($this->logger);
        foreach ($schedule->getAgainstOneVsTwoSchedules() as $scheduleAgainstOneVsTwo) {
            $rootCycles[] = $cycleCreatorAgainstOneVsTwo->createRootCycleAndGames($scheduleAgainstOneVsTwo);
        }

        $cycleCreatorAgainstTwoVsTwo = new CycleCreatorAgainstTwoVsTwo($this->logger);
        foreach ($schedule->getAgainstTwoVsTwoSchedules() as $scheduleAgainstTwoVsTwo) {
            $rootCycles[] = $cycleCreatorAgainstTwoVsTwo->createRootCycleAndGames(
                $scheduleAgainstTwoVsTwo);
        }
        return $rootCycles;

        //            $againstVariantsWithNr = $this->convertToAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
        //            $againstVariantMap = $this->convertToAgainstVariantMap($againstVariantsWithNr);
        //            if( count($againstVariantMap) > 0) {
        //                $differenceManager = new AgainstDifferenceManager(
        //                    count($poule->getPlaces()),
        //                    $againstVariantMap,
        //                    $allowedGppMargin,
        //                    $this->logger);
        //
        //                $againstH2hsWithNr = $this->convertToAgainstH2hSportVariantsWithNr($sportVariantsWithNr);
        //                $againstGppsWithNr = $this->convertToAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
        //
        //                $homeNrCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
        //                if( $h2hScheduleSport !== null ) {
        //
        //                } else if( count($againstGppsWithNr) > 0) {
        //                    $againstGppScheduleCreator = new AgainstGppScheduleCreator($this->logger);
        //                    $againstGppScheduleCreator->createSportSchedules(
        //                        $schedule,
        //                        $againstGppsWithNr,
        //                        $homeNrCounterMap,
        //                        $togetherCounterMap,
        //                        $differenceManager,
        //                        $nrOfSecondsBeforeTimeout);
        //                }
        //            }
    }

//    public function createBetterSchedule(
//        Schedule $schedule,
//        int $allowedGppMargin,
//        int $nrOfSecondsBeforeTimeout): Schedule
//    {
//        $nrOfPlaces = $schedule->getNrOfPlaces();
//        $sportVariants = $schedule->createSportVariants();
//        $oldSportSchedules = array_values($schedule->getSportSchedules()->toArray());
//        $sportVariantsWithNr = $this->createSportVariantsWithNr($oldSportSchedules);
//        $newSchedule = new Schedule($nrOfPlaces, $sportVariantsWithNr);
//
//        // AllInOneGame
//        (new AllInOneGameScheduleCreator())->createGamesForSports($newSchedule);
//
//        $singleHelper = new SingleCreatorHelper($this->logger);
//        $togetherCounterMap = $singleHelper->createGamesForSports($newSchedule);
//
//        $scheduleSportH2h = $this->filterH2hSport($schedule);
//
//        if( $scheduleSportH2h !== null) {
//            $againstH2hScheduleCreator = new AgainstH2hScheduleCreator($this->logger);
//            $againstH2hScheduleCreator->createGamesForSport($scheduleSportH2h);
//        } else if ( count($this->filterGppSports($newSchedule)) > 0) {
//            $againstGppScheduleCreator = new AgainstGppScheduleCreator($this->logger);
//            $againstGppScheduleCreator->createGamesForSports($newSchedule, $togetherCounterMap);
//        }


//        $assignedCounter = new AssignedCounter($newPoule, $sportVariants);
//        $assignedCounter = $assignedCounter->addWithMap($withMap);

        // AgainstH2h|AgainstGpp
//        {
//            $againstVariantsWithNr = $this->convertToAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
//            $againstVariantMap = $this->convertToAgainstVariantMap($againstVariantsWithNr);
//            if( count($againstVariantMap) > 0) {
//
//                $differenceManager = new AgainstDifferenceManager($nrOfPlaces, $againstVariantMap, $allowedGppMargin, $this->logger);
//
//                $homeCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
//                $againstH2hsWithNr = $this->convertToAgainstH2hSportVariantsWithNr($againstVariantsWithNr);
//                if( count($againstH2hsWithNr) > 0 ) {
//                    // @TODO CDK Against    => NVP
//                    // @TODO CDK With       => Deze vullen voor together
//                    // @TODO CDK Home       => NVP
//                    // Pas counter toe als voor de sport het item op ongelijk uitkomt
//                    $againstH2hScheduleCreator = new AgainstH2hScheduleCreator($this->logger);
//                    $homeCounterMap = $againstH2hScheduleCreator->createSportSchedules(
//                        $newSchedule, $againstH2hsWithNr, $differenceManager);
//                }
//                $againstGppsWithNr = $this->convertToAgainstGppSportVariantsWithNr($againstVariantsWithNr, $schedule->getNrOfPlaces());
//                if( count($againstGppsWithNr) > 0 ) {
//                    $againstGppScheduleCreator = new AgainstGppScheduleCreator($this->logger);
//                    $againstGppScheduleCreator->createSportSchedules(
//                        $newSchedule, $againstGppsWithNr,
//                        $homeCounterMap, $togetherCounterMap,
//                        $differenceManager, $nrOfSecondsBeforeTimeout);
//                }
//            }
//        }
//
//        return $newSchedule;
//    }

//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @return list<SportVariantWithNr>
//     */
//    public function getAllInOneGameSportVariantsWithNr(array $sportVariantsWithNr): array
//    {
//        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
//            return $sportVariantWithNr->sportVariant instanceof AllInOneGame;
//        }));
//    }

//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @return list<SportVariantWithNr>
//     */
//    private function convertToSingleSportVariantsWithNr(array $sportVariantsWithNr): array
//    {
//        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
//            return $sportVariantWithNr->sportVariant instanceof Single;
//        }));
//    }

//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @return list<SportVariantWithNr>
//     */
//    private function convertToAgainstH2hSportVariantsWithNr(array $sportVariantsWithNr): array
//    {
//        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
//            return $sportVariantWithNr->sportVariant instanceof AgainstH2h;
//        }));
//    }

//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @param int $nrOfPlaces
//     * @return list<SportVariantWithNr>
//     */
//    private function convertToAgainstGppSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
//    {
//        $newSportVariantsWithNr = array_values( array_filter( $sportVariantsWithNr,
//            function(SportVariantWithNr $sportVariantWithNr): bool {
//                return $sportVariantWithNr->sportVariant instanceof AgainstGpp;
//            }));
//        return $this->sortByEquallyAssigned($newSportVariantsWithNr, $nrOfPlaces);
//    }


//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @param int $nrOfPlaces
//     * @return list<SportVariantWithNr>
//     */
//    private function convertToAgainstSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
//    {
//        $againstVariantsWithNr = [];
//        foreach( $this->convertToAgainstH2hSportVariantsWithNr($sportVariantsWithNr) as $againstH2hWithNr) {
//            $againstVariantsWithNr[] = $againstH2hWithNr;
//        }
//        foreach( $this->convertToAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces) as $againstGppWithNr) {
//            $againstVariantsWithNr[] = $againstGppWithNr;
//        }
//        return $againstVariantsWithNr;
//    }

//    /**
//     * @param list<SportVariantWithNr> $sportVariantsWithNr
//     * @return array<int, AgainstH2h|AgainstGpp>
//     */
//    private function convertToAgainstVariantMap(array $sportVariantsWithNr): array {
//        $againstVariantMap = [];
//        foreach( $sportVariantsWithNr as $sportVariantWithNr) {
//            if( ($sportVariantWithNr->sportVariant instanceof AgainstGpp)
//                || ($sportVariantWithNr->sportVariant instanceof AgainstH2h)) {
//                $againstVariantMap[$sportVariantWithNr->number] = $sportVariantWithNr->sportVariant;
//            }
//        }
//        return $againstVariantMap;
//    }

//    private function sortByEquallyAssigned(array $sportVariantsWithNr, int $nrOfPlaces): array
//    {
//        uasort($sportVariantsWithNr,
//            function (SportVariantWithNr $sportVariantWithNrA, SportVariantWithNr $sportVariantWithNrB) use($nrOfPlaces): int {
//                $sportVariantA = $sportVariantWithNrA->sportVariant;
//                $sportVariantB = $sportVariantWithNrB->sportVariant;
//                if ( !( $sportVariantA instanceof AgainstGpp)
//                    || !($sportVariantB instanceof AgainstGpp) ) {
//                    return 0;
//                    }
//                $sportVariantWithNrOfPlacesA = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariantA );
//                $sportVariantWithNrOfPlacesB = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariantB );
//                $allPlacesSameNrOfGamesA = $sportVariantWithNrOfPlacesA->allPlacesSameNrOfGamesAssignable();
//                $allPlacesSameNrOfGamesB = $sportVariantWithNrOfPlacesB->allPlacesSameNrOfGamesAssignable();
//                if (($allPlacesSameNrOfGamesA && $allPlacesSameNrOfGamesB)
//                    || (!$allPlacesSameNrOfGamesA && !$allPlacesSameNrOfGamesB)) {
//                    return 0;
//                }
//                return $allPlacesSameNrOfGamesA ? -1 : 1;
//        });
//        return array_values($sportVariantsWithNr);
//    }


//
//    /**
//     * @param list<ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo|ScheduleTogetherSport> $sports
//     * @return array<int, AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport>
//     */
//    private function createMapFromSportSchedules( array $sports ): array {
//        $retVal = [];
//        foreach( $sports as $sport ) {
//            $retVal[$sport->getNumber()] = $sport->sport;
//        }
//        return $retVal;
//    }
//
//    /**
//     * @param list<AgainstPlannableOneVsOne|AgainstPlannableOneVsTwo|AgainstPlannableTwoVsTwo|PlannableTogetherSport> $sports
//     * @return array<int, AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport>
//     */
//    private function createMapFromPlannableSports( array $sports ): array {
//        $retVal = [];
//        foreach( $sports as $sport ) {
//            $retVal[$sport->getNumber()] = $sport->sport;
//        }
//        return $retVal;
//    }
}
