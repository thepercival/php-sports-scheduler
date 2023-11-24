<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\AssignedCounter;
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
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use stdClass;


class Creator
{
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;


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
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, (string)$sportConfigsName)) {
                continue;
            }
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $sportVariants);
            $schedules[$nrOfPlaces] = $schedule;

            $allInOneGameSportVariantsWithNr = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createSportSchedules($schedule, $poule, $allInOneGameSportVariantsWithNr);

            $assignedCounter = new AssignedCounter($poule, $sportVariants);
            $singleSportVariantsWithNr = $this->getSingleSportVariantsWithNr($sportVariantsWithNr);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $singleHelper->createSportSchedules($schedule, $poule, $singleSportVariantsWithNr, $assignedCounter);

            $againstVariantsWithNr = $this->getAgainstSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
            if( count($againstVariantsWithNr) > 0) {
                $differenceManager = new AgainstDifferenceManager(
                    $poule,
                    $againstVariantsWithNr,
                    $allowedGppMargin,
                    $this->logger);

                $againstH2hsWithNr = $this->getAgainstH2hSportVariantsWithNr($sportVariantsWithNr);
                if( count($againstH2hsWithNr) > 0 ) {
                    $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                    $againstH2hHelper->createSportSchedules(
                        $schedule,
                        $poule,
                        $againstH2hsWithNr,
                        $assignedCounter,
                        $differenceManager);
                }
                $againstGppsWithNr = $this->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
                if( count($againstGppsWithNr) > 0) {
                    $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createSportSchedules(
                        $schedule,
                        $poule,
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
        $sportVariants = $schedule->createSportVariants();
        $oldSportSchedules = array_values($schedule->getSportSchedules()->toArray());
        $sportVariantsWithNr = $this->createSportVariantsWithNr($oldSportSchedules);
        $newSchedule = new Schedule($schedule->getNrOfPlaces(), $sportVariants);

        $newPoule = (new Input( new Input\Configuration(
                new PouleStructure( $schedule->getNrOfPlaces() ),
                $schedule->createSportVariantWithFields(),
                new Info(),
                false
        )))->getPoule(1);

        $assignedCounter = new AssignedCounter($newPoule, $sportVariants);

        // AllInOneGame
        {
            $allInOneGameSportVariantMap = $this->getAllInOneGameSportVariantsWithNr($sportVariantsWithNr);
            (new AllInOneGameCreatorHelper())->createSportSchedules($newSchedule, $newPoule, $allInOneGameSportVariantMap);
        }

        // Single
        {
            $singleSportVariantsWithNr = $this->getSingleSportVariantsWithNr($sportVariantsWithNr);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $singleHelper->createSportSchedules($newSchedule, $newPoule, $singleSportVariantsWithNr, $assignedCounter);
        }

        // AgainstH2h|AgainstGpp
        {
            $againstVariantsWithNr = $this->getAgainstSportVariantsWithNr($sportVariantsWithNr, $schedule->getNrOfPlaces());

            if( count($againstVariantsWithNr) > 0) {
                $differenceManager = new AgainstDifferenceManager($newPoule, $againstVariantsWithNr, $allowedGppMargin, $this->logger);

                $againstH2hsWithNr = $this->getAgainstH2hSportVariantsWithNr($againstVariantsWithNr);
                if( count($againstH2hsWithNr) > 0 ) {
                    $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                    $againstH2hHelper->createSportSchedules(
                        $newSchedule, $newPoule, $againstH2hsWithNr, $assignedCounter, $differenceManager);
                }
                $againstGppsWithNr = $this->getAgainstGppSportVariantsWithNr($againstVariantsWithNr, $schedule->getNrOfPlaces());
                if( count($againstGppsWithNr) > 0 ) {
                    $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createSportSchedules(
                        $newSchedule, $newPoule, $againstGppsWithNr,
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

        // Single
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
     * @param list<SportSchedule|Sport> $sportSchedules
     * @return list<SportVariantWithNr>
     */
    public function createSportVariantsWithNr( array $sportSchedules ): array {
        return array_map(function(SportSchedule|Sport $sportSchedule): SportVariantWithNr {
            return new SportVariantWithNr($sportSchedule->getNumber(), $sportSchedule->createVariant() );
            }
        , $sportSchedules);
    }

}
