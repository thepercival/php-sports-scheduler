<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\EquallyAssignCalculator;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
// use SportsPlanning\Counters\AssignedCounter;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\Sport;
use SportsScheduler\Schedule\CreatorHelpers\Against\H2h as AgainstH2hCreatorHelper;
use SportsScheduler\Schedule\CreatorHelpers\Against\GamesPerPlace as AgainstGppCreatorHelper;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\CreatorHelpers\AllInOneGame as AllInOneGameCreatorHelper;
use SportsScheduler\Schedule\CreatorHelpers\Single as SingleCreatorHelper;
use SportsPlanning\Schedule\Name as ScheduleName;

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
        $sportConfigsName = new ScheduleName($sportVariants);
        foreach ($distinctPoules as $poule) {
            $nrOfPlaces = count($poule->getPlaces());
            $pouleStructureBase = new PouleStructure($nrOfPlaces);
            if( $pouleStructureBase->getTotalNrOfGames($sportVariants) > self::MaxNrOfGames ) {
                throw new \Exception('the maximum number of games is ' . self::MaxNrOfGames );
            }
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, (string)$sportConfigsName)) {
                continue;
            }
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $sportVariants);
            $schedules[$nrOfPlaces] = $schedule;

            $allInOneGameSportVariantsWithNr = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createSportSchedules($schedule, $allInOneGameSportVariantsWithNr);

            $singleSportVariantsWithNr = $this->convertToSingleSportVariantsWithNr($sportVariantsWithNr);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $togetherCounterMap = $singleHelper->createSportSchedules($schedule, $singleSportVariantsWithNr);

            $againstVariantsWithNr = $this->convertToAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            $againstVariantMap = $this->convertToAgainstVariantMap($againstVariantsWithNr);
            if( count($againstVariantMap) === 0) {
                continue;
            }
            $differenceManager = new AgainstDifferenceManager(
                count($poule->getPlaces()),
                $againstVariantMap,
                $allowedGppMargin,
                $this->logger);

            $againstH2hsWithNr = $this->convertToAgainstH2hSportVariantsWithNr($sportVariantsWithNr);

            $homeNrCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
            if( count($againstH2hsWithNr) > 0 ) {
                $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                $homeNrCounterMap = $againstH2hHelper->createSportSchedules(
                    $schedule,
                    $againstH2hsWithNr,
                    $differenceManager);
            }
            $againstGppsWithNr = $this->convertToAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            if( count($againstGppsWithNr) > 0) {
                $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                $againstGppHelper->createSportSchedules(
                    $schedule,
                    $againstGppsWithNr,
                    $homeNrCounterMap,
                    $togetherCounterMap,
                    $differenceManager,
                    $nrOfSecondsBeforeTimeout);
            }
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
        $oldSportSchedules = array_values($schedule->getSportSchedules()->toArray());
        $sportVariantsWithNr = $this->createSportVariantsWithNr($oldSportSchedules);
        $newSchedule = new Schedule($nrOfPlaces, $sportVariants);

        // AllInOneGame
        {
            $allInOneGameSportVariantMap = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createSportSchedules($newSchedule, $allInOneGameSportVariantMap);
        }

        // Single
        $singleSportVariantsWithNr = $this->convertToSingleSportVariantsWithNr($sportVariantsWithNr);
        $singleHelper = new SingleCreatorHelper($this->logger);
        $togetherCounterMap = $singleHelper->createSportSchedules($newSchedule, $singleSportVariantsWithNr);

//        $assignedCounter = new AssignedCounter($newPoule, $sportVariants);
//        $assignedCounter = $assignedCounter->addWithMap($withMap);

        // AgainstH2h|AgainstGpp
        {
            $againstVariantsWithNr = $this->convertToAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            $againstVariantMap = $this->convertToAgainstVariantMap($againstVariantsWithNr);
            if( count($againstVariantMap) > 0) {

                $differenceManager = new AgainstDifferenceManager($nrOfPlaces, $againstVariantMap, $allowedGppMargin, $this->logger);

                $homeCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
                $againstH2hsWithNr = $this->convertToAgainstH2hSportVariantsWithNr($againstVariantsWithNr);
                if( count($againstH2hsWithNr) > 0 ) {
                    // @TODO CDK Against    => NVP
                    // @TODO CDK With       => Deze vullen voor together
                    // @TODO CDK Home       => NVP
                    // Pas counter toe als voor de sport het item op ongelijk uitkomt
                    $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                    $homeCounterMap = $againstH2hHelper->createSportSchedules(
                        $newSchedule, $againstH2hsWithNr, $differenceManager);
                }
                $againstGppsWithNr = $this->convertToAgainstGppSportVariantsWithNr($againstVariantsWithNr, $schedule->getNrOfPlaces());
                if( count($againstGppsWithNr) > 0 ) {
                    $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createSportSchedules(
                        $newSchedule, $againstGppsWithNr,
                        $homeCounterMap, $togetherCounterMap,
                        $differenceManager, $nrOfSecondsBeforeTimeout);
                }
            }
        }

        return $newSchedule;
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
     * @param list<SportSchedule|Sport> $sportSchedules
     * @return list<SportVariantWithNr>
     */
    private function createSportVariantsWithNr( array $sportSchedules ): array {
        return array_map(function(SportSchedule|Sport $sportSchedule): SportVariantWithNr {
            return new SportVariantWithNr($sportSchedule->getNumber(), $sportSchedule->createVariant() );
            }
        , $sportSchedules);
    }
}
