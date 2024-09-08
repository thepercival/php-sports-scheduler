<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\GameMode;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\SportVariants\AgainstGpp;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\ScheduleSport;
use SportsPlanning\Schedule\SportVariantWithNr;
use SportsPlanning\Sport;
use SportsScheduler\Schedule\SportScheduleCreators\AgainstGppScheduleCreator;
use SportsScheduler\Schedule\SportScheduleCreators\AgainstH2hScheduleCreator;
use SportsScheduler\Schedule\SportScheduleCreators\AllInOneGameScheduleCreator;
use SportsScheduler\Schedule\SportScheduleCreators\Helpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\SportScheduleCreators\SingleScheduleCreator as SingleCreatorHelper;

// use SportsPlanning\Counters\AssignedCounter;

class Creator
{
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;
    public const int MaxNrOfSports = 12;
    public const int MaxNrOfGames = 496;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @param int $allowedGppMargin
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromInput(
        Input $input,
        int $allowedGppMargin,
        int|null $nrOfSecondsBeforeTimeout = null): array
    {
        $sports = array_values( $input->getSports()->toArray() );
        $sportVariantsWithNr = $this->createSportVariantsWithNr( $sports );

        $distinctPoules = [];
        foreach( $input->getPoules() as $poule) {
            $nrOfPlaces = count($poule->getPlaces());
            if( !array_key_exists($nrOfPlaces, $distinctPoules ) ) {
                $distinctPoules[$nrOfPlaces] = $poule;
            }
        }

        return $this->createFromInputHelper(
            $sportVariantsWithNr,
            array_values($distinctPoules),
            $allowedGppMargin,
            $nrOfSecondsBeforeTimeout);
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param list<Poule> $distinctPoules
     * @param int $allowedGppMargin
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromInputHelper(
        array $sportVariantsWithNr,
        array $distinctPoules,
        int $allowedGppMargin,
        int|null $nrOfSecondsBeforeTimeout = null): array
    {
        if( count($sportVariantsWithNr) > self::MaxNrOfSports ) {
            throw new \Exception('the maximum number of sports is ' . self::MaxNrOfSports );
        }
        if( $allowedGppMargin > 2 ) {
            $allowedGppMargin = 2;
        }
        /** @var array<int, Schedule> $schedules */
        $schedules = [];
        $sportVariants = array_map(
            function(SportVariantWithNr $sportVariantsWithNr): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $sportVariantsWithNr->sportVariant;
            }, $sportVariantsWithNr );
        foreach ($distinctPoules as $poule) {
            $nrOfPlaces = count($poule->getPlaces());
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, $sportVariantsWithNr)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $sportVariantsWithNr);
            $pouleStructureBase = new PouleStructure($nrOfPlaces);
            if( $pouleStructureBase->getTotalNrOfGames($sportVariants) > self::MaxNrOfGames ) {
                throw new \Exception('the maximum number of games is ' . self::MaxNrOfGames );
            }


            (new AllInOneGameScheduleCreator())->createGamesForSports($schedule);

            $singleHelper = new SingleCreatorHelper($this->logger);
            $togetherCounterMap = $singleHelper->createGamesForSports($schedule);

            $scheduleSportH2h = $this->filterH2hSport($schedule);

            if( $scheduleSportH2h !== null) {
                $againstH2hScheduleCreator = new AgainstH2hScheduleCreator($this->logger);
                $againstH2hScheduleCreator->createGamesForSport($scheduleSportH2h);
            } else if ( count($this->filterGppSports($schedule)) > 0) {
                $againstGppScheduleCreator = new AgainstGppScheduleCreator($this->logger);
                $againstGppScheduleCreator->createGamesForSports($schedule, $togetherCounterMap);
            }

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

            $schedules[$nrOfPlaces] = $schedule;
        }
        return array_values($schedules);
    }


    private function filterH2hSport(Schedule $schedule): ScheduleSport|null {
        $filtered = array_filter($schedule->getSportSchedules()->toArray(), function(ScheduleSport $scheduleSport): bool {
            return $scheduleSport->getGameMode() === GameMode::Against && $scheduleSport->getNrOfH2h() > 0;
        } );
        $filteredOne = reset($filtered);
        return $filteredOne === false ? null : $filteredOne;
    }

    /**
     * @param Schedule $schedule
     * @return list<ScheduleSport>
     */
    private function filterGppSports(Schedule $schedule): array {
        $scheduleSports = array_values( $schedule->getSportSchedules()->toArray() );
        return array_values( array_filter($scheduleSports, function(ScheduleSport $scheduleSport): bool {
            return $scheduleSport->getGameMode() === GameMode::Against && $scheduleSport->getNrOfGamesPerPlace() > 0;
        } ) );
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

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return list<SportVariantWithNr>
     */
    public function getAllInOneGameSportVariantsWithNr(array $sportVariantsWithNr): array
    {
        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
            return $sportVariantWithNr->sportVariant instanceof AllInOneGame;
        }));
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return list<SportVariantWithNr>
     */
    private function convertToSingleSportVariantsWithNr(array $sportVariantsWithNr): array
    {
        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
            return $sportVariantWithNr->sportVariant instanceof Single;
        }));
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return list<SportVariantWithNr>
     */
    private function convertToAgainstH2hSportVariantsWithNr(array $sportVariantsWithNr): array
    {
        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
            return $sportVariantWithNr->sportVariant instanceof AgainstH2h;
        }));
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param int $nrOfPlaces
     * @return list<SportVariantWithNr>
     */
    private function convertToAgainstGppSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
    {
        $newSportVariantsWithNr = array_values( array_filter( $sportVariantsWithNr,
            function(SportVariantWithNr $sportVariantWithNr): bool {
                return $sportVariantWithNr->sportVariant instanceof AgainstGpp;
            }));
        return $this->sortByEquallyAssigned($newSportVariantsWithNr, $nrOfPlaces);
    }


    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param int $nrOfPlaces
     * @return list<SportVariantWithNr>
     */
    private function convertToAgainstSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
    {
        $againstVariantsWithNr = [];
        foreach( $this->convertToAgainstH2hSportVariantsWithNr($sportVariantsWithNr) as $againstH2hWithNr) {
            $againstVariantsWithNr[] = $againstH2hWithNr;
        }
        foreach( $this->convertToAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces) as $againstGppWithNr) {
            $againstVariantsWithNr[] = $againstGppWithNr;
        }
        return $againstVariantsWithNr;
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return array<int, AgainstH2h|AgainstGpp>
     */
    private function convertToAgainstVariantMap(array $sportVariantsWithNr): array {
        $againstVariantMap = [];
        foreach( $sportVariantsWithNr as $sportVariantWithNr) {
            if( ($sportVariantWithNr->sportVariant instanceof AgainstGpp)
                || ($sportVariantWithNr->sportVariant instanceof AgainstH2h)) {
                $againstVariantMap[$sportVariantWithNr->number] = $sportVariantWithNr->sportVariant;
            }
        }
        return $againstVariantMap;
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param int $nrOfPlaces
     * @return list<SportVariantWithNr>
     */
    private function sortByEquallyAssigned(array $sportVariantsWithNr, int $nrOfPlaces): array
    {
        uasort($sportVariantsWithNr,
            function (SportVariantWithNr $sportVariantWithNrA, SportVariantWithNr $sportVariantWithNrB) use($nrOfPlaces): int {
                $sportVariantA = $sportVariantWithNrA->sportVariant;
                $sportVariantB = $sportVariantWithNrB->sportVariant;
                if ( !( $sportVariantA instanceof AgainstGpp)
                    || !($sportVariantB instanceof AgainstGpp) ) {
                    return 0;
                    }
                $sportVariantWithNrOfPlacesA = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariantA );
                $sportVariantWithNrOfPlacesB = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariantB );
                $allPlacesSameNrOfGamesA = $sportVariantWithNrOfPlacesA->allPlacesSameNrOfGamesAssignable();
                $allPlacesSameNrOfGamesB = $sportVariantWithNrOfPlacesB->allPlacesSameNrOfGamesAssignable();
                if (($allPlacesSameNrOfGamesA && $allPlacesSameNrOfGamesB)
                    || (!$allPlacesSameNrOfGamesA && !$allPlacesSameNrOfGamesB)) {
                    return 0;
                }
                return $allPlacesSameNrOfGamesA ? -1 : 1;
        });
        return array_values($sportVariantsWithNr);
    }


    /**
     * @param list<Schedule> $existingSchedules
     */
    public function setExistingSchedules(array $existingSchedules): void
    {
        $this->existingSchedules = $existingSchedules;
    }

    /**
     * @param int $nrOfPlaces
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return bool
     */
    public function isScheduleAlreadyCreated(int $nrOfPlaces, array $sportVariantsWithNr): bool
    {
        if ($this->existingSchedules === null) {
            return false;
        }
        foreach ($this->existingSchedules as $existingSchedule) {
            if ((new Schedule($nrOfPlaces, $sportVariantsWithNr))->toJsonCustom() === $existingSchedule->toJsonCustom()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<ScheduleSport|Sport> $sportSchedules
     * @return list<SportVariantWithNr>
     */
    private function createSportVariantsWithNr( array $sportSchedules ): array {
        return array_map(function(ScheduleSport|Sport $sportSchedule): SportVariantWithNr {
            return new SportVariantWithNr($sportSchedule->getNumber(), $sportSchedule->createVariant() );
            }
        , $sportSchedules);
    }
}
