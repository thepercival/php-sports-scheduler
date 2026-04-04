<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleName;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleAgainstGppCreatorHelper;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleAgainstH2hCreatorHelper;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleAllInOneGameCreatorHelper as AllInOneGameCreatorHelper;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleSingleGameCreatorHelper as SingleCreatorHelper;
use stdClass;


final class ScheduleCreator
{
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param PouleStructure $pouleStructure ,
     * @param list<SportVariantWithNr> $sportVariantsWithNr,
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromPouleStructureAndSports(
        PouleStructure $pouleStructure,
        array $sportVariantsWithNr,
        int $allowedGppMargin,
        int|null $nrOfSecondsBeforeTimeout = null): array
    {
        $distinctNrOfPlaces = [];
        foreach( $pouleStructure->toArray() as $nrOfPlaces) {
            if( !array_key_exists($nrOfPlaces, $distinctNrOfPlaces ) ) {
                $distinctNrOfPlaces[$nrOfPlaces] = $nrOfPlaces;
            }
        }
        $distinctNrOfPlaces = array_values($distinctNrOfPlaces);

        if( $allowedGppMargin > 2 ) {
            $allowedGppMargin = 2;
        }
        /** @var array<int, Schedule> $schedules */
        $schedules = [];
        $sportVariants = array_map(
            function(SportVariantWithNr $sportVariantsWithNr): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $sportVariantsWithNr->sportVariant;
            }, $sportVariantsWithNr );
        $sportConfigsName = new ScheduleName($sportVariants);
        foreach ($distinctNrOfPlaces as $nrOfPlaces) {
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, (string)$sportConfigsName)) {
                continue;
            }
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $sportVariants);
            $schedules[$nrOfPlaces] = $schedule;

            $allInOneGameSportVariantsWithNr = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createScheduleSports($schedule, $nrOfPlaces, $allInOneGameSportVariantsWithNr);

            $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
            $singleSportVariantsWithNr = $this->getSingleSportVariantsWithNr($sportVariantsWithNr);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $singleHelper->createScheduleSports($schedule, $nrOfPlaces, $singleSportVariantsWithNr, $assignedCounter);

            $againstVariantsWithNr = $this->getAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            if( count($againstVariantsWithNr) > 0) {
                $differenceManager = new AgainstDifferenceManager(
                    $nrOfPlaces,
                    $againstVariantsWithNr,
                    $allowedGppMargin,
                    $this->logger);

                $againstH2hsWithNr = $this->getAgainstH2hSportVariantsWithNr($sportVariantsWithNr);
                if( count($againstH2hsWithNr) > 0 ) {
                    $againstH2hHelper = new ScheduleAgainstH2hCreatorHelper($this->logger);
                    $againstH2hHelper->createScheduleSports(
                        $schedule,
                        $nrOfPlaces,
                        $againstH2hsWithNr,
                        $assignedCounter,
                        $differenceManager);
                }
                $againstGppsWithNr = $this->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
                if( count($againstGppsWithNr) > 0) {
                    $againstGppHelper = new ScheduleAgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createScheduleSports(
                        $schedule,
                        $nrOfPlaces,
                        $againstGppsWithNr,
                        $assignedCounter,
                        $differenceManager,
                        $nrOfSecondsBeforeTimeout);
                }
            }
//            try {
//            } catch(LessThanMinimumAgainstDifferenceException $e) {
//
//            }
        }
        return array_values($schedules);
    }



    public function createBetterSchedule(
        Schedule $schedule,
        int $allowedGppMargin,
        int $nrOfSecondsBeforeTimeout): Schedule
    {
        $nrOfPlaces = $schedule->getNrOfPlaces();
        $sportVariants = $schedule->createSportVariants();
        $oldScheduleSports = array_values($schedule->getScheduleSports()->toArray());
        $sportVariantsWithNr = $this->createSportVariantsWithNrFromScheduleSports($oldScheduleSports);
        $newSchedule = new Schedule($nrOfPlaces, $sportVariants);

//        $newPoule = (new Input( new Input\Configuration(
//                new PouleStructure( $schedule->getNrOfPlaces() ),
//                $schedule->createSportVariantWithFields(),
//                new PlanningRefereeInfo(),
//                false
//        )))->getPoule(1);

        $assignedCounter = new AssignedCounter($schedule->getNrOfPlaces(), $sportVariants);

        // ScheduleAllInOneGameCreatorHelper
        {
            $allInOneGameSportVariantMap = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createScheduleSports($newSchedule, $schedule->getNrOfPlaces(), $allInOneGameSportVariantMap);
        }

        // SingleGameRoundCreator
        {
            $singleSportVariantsWithNr = $this->getSingleSportVariantsWithNr($sportVariantsWithNr);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $singleHelper->createScheduleSports($newSchedule, $schedule->getNrOfPlaces(), $singleSportVariantsWithNr, $assignedCounter);
        }

        // AgainstH2h|AgainstGpp
        {
            $againstVariantsWithNr = $this->getAgainstSportVariantsWithNr($sportVariantsWithNr, $schedule->getNrOfPlaces());

            if( count($againstVariantsWithNr) > 0) {
                $differenceManager = new AgainstDifferenceManager($nrOfPlaces, $againstVariantsWithNr, $allowedGppMargin, $this->logger);

                $againstH2hsWithNr = $this->getAgainstH2hSportVariantsWithNr($againstVariantsWithNr);
                if( count($againstH2hsWithNr) > 0 ) {
                    $againstH2hHelper = new ScheduleAgainstH2hCreatorHelper($this->logger);
                    $againstH2hHelper->createScheduleSports(
                        $newSchedule, $nrOfPlaces, $againstH2hsWithNr, $assignedCounter, $differenceManager);
                }
                $againstGppsWithNr = $this->getAgainstGppSportVariantsWithNr($againstVariantsWithNr, $schedule->getNrOfPlaces());
                if( count($againstGppsWithNr) > 0 ) {
                    $againstGppHelper = new ScheduleAgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createScheduleSports(
                        $newSchedule, $nrOfPlaces, $againstGppsWithNr,
                        $assignedCounter, $differenceManager, $nrOfSecondsBeforeTimeout);
                }
            }
        }

        return $newSchedule;
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param int $nrOfPlaces
     * @return int
     */
    public function getMaxGppMargin(array $sportVariantsWithNr, int $nrOfPlaces): int {
        $maxAgainstMargin = 0;
        $maxWithMargin = 0;

        // AgainstGpp
        {
            $againstGppsWithNr = $this->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            if( count($againstGppsWithNr) > 0 ) {
                $margins = $this->getMargins($nrOfPlaces, $againstGppsWithNr);
                /** @var int $maxWithMargin */
                $maxWithMargin = $margins->maxWithMargin;
                /** @var int $maxAgainstMargin */
                $maxAgainstMargin = $margins->maxAgainstMargin;
            }
        }

        // SingleGameRoundCreator
        {
            $singlesWithNr = $this->getSingleSportVariantsWithNr($sportVariantsWithNr);
            if( count($singlesWithNr) > 0 ) {
                $maxWithMargin = max(1, $maxWithMargin);
            }
        }

        return max($maxAgainstMargin, $maxWithMargin);
    }

    /**
     * @param int $nrOfPlaces
     * @param list<SportVariantWithNr> $againstGppsWithNr
     * @return stdClass
     */
    private function getMargins(int $nrOfPlaces, array $againstGppsWithNr): stdClass {
        $allowedAgainstAmountCum = 0;
        $nrOfAgainstCombinationsCumulative = 0;
        $allowedWithAmountCum = 0;
        $nrOfWithCombinationsCumulative = 0;
        foreach ($againstGppsWithNr as $againstGppWithNr) {
            $againstGpp = $againstGppWithNr->sportVariant;
            if( !($againstGpp instanceof AgainstGpp) ) {
                continue;
            }
            $againstGppWithPoule = new AgainstGppWithPoule($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();
            // against
            {
                $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
                $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;
                $allowedAgainstAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfAgainstCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
                );
            }
            // with
            {
                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;
                $allowedWithAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );
            }
        }
        $margins = new stdClass();
        $margins->maxWithMargin = $allowedAgainstAmountCum;
        $margins->maxAgainstMargin = $allowedWithAmountCum;
        return $margins;
    }


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
    public function getSingleSportVariantsWithNr(array $sportVariantsWithNr): array
    {
        return array_values( array_filter( $sportVariantsWithNr, function(SportVariantWithNr $sportVariantWithNr): bool {
            return $sportVariantWithNr->sportVariant instanceof Single;
        }));
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @return list<SportVariantWithNr>
     */
    public function getAgainstH2hSportVariantsWithNr(array $sportVariantsWithNr): array
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
    public function getAgainstGppSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
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
    protected function getAgainstSportVariantsWithNr(array $sportVariantsWithNr, int $nrOfPlaces): array
    {
        $againstVariantsWithNr = [];
        foreach( $this->getAgainstH2hSportVariantsWithNr($sportVariantsWithNr) as $againstH2hWithNr) {
            $againstVariantsWithNr[] = $againstH2hWithNr;
        }
        foreach( $this->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces) as $againstGppWithNr) {
            $againstVariantsWithNr[] = $againstGppWithNr;
        }
        return $againstVariantsWithNr;
    }

    /**
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     * @param int $nrOfPlaces
     * @return list<SportVariantWithNr>
     */
    protected function sortByEquallyAssigned(array $sportVariantsWithNr, int $nrOfPlaces): array
    {
        uasort($sportVariantsWithNr,
            function (SportVariantWithNr $sportVariantWithNrA, SportVariantWithNr $sportVariantWithNrB) use($nrOfPlaces): int {
                $sportVariantA = $sportVariantWithNrA->sportVariant;
                $sportVariantB = $sportVariantWithNrB->sportVariant;
                if ( !( $sportVariantA instanceof AgainstGpp)
                    || !($sportVariantB instanceof AgainstGpp) ) {
                    return 0;
                    }
                $sportVariantWithPouleA = new AgainstGppWithPoule($nrOfPlaces, $sportVariantA );
                $sportVariantWithPouleB = new AgainstGppWithPoule($nrOfPlaces, $sportVariantB );
                $allPlacesSameNrOfGamesA = $sportVariantWithPouleA->allPlacesSameNrOfGamesAssignable();
                $allPlacesSameNrOfGamesB = $sportVariantWithPouleB->allPlacesSameNrOfGamesAssignable();
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

    public function isScheduleAlreadyCreated(int $nrOfPlaces, string $sportConfigsName): bool
    {
        if ($this->existingSchedules === null) {
            return false;
        }
        foreach ($this->existingSchedules as $existingSchedule) {
            if ($nrOfPlaces === $existingSchedule->getNrOfPlaces()
                && $sportConfigsName === $existingSchedule->getSportsConfigName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<AgainstGpp|AgainstH2h|Single|AllInOneGame> $sportVariants
     * @return list<SportVariantWithNr>
     */
    public function createSportVariantsWithNr( array $sportVariants ): array {
        $sportNr = 1;
        return array_map(function(AgainstGpp|AgainstH2h|Single|AllInOneGame $sportVariant) use(&$sportNr): SportVariantWithNr {
            return new SportVariantWithNr($sportNr++, $sportVariant );
        }
        , $sportVariants);
    }

    /**
     * @param list<ScheduleSport> $scheduleSports
     * @return list<SportVariantWithNr>
     */
    public function createSportVariantsWithNrFromScheduleSports( array $scheduleSports ): array {
        return array_map(function(ScheduleSport $scheduleSport): SportVariantWithNr {
            return new SportVariantWithNr($scheduleSport->getNumber(), $scheduleSport->createVariant() );
        }
        , $scheduleSports);
    }

}
