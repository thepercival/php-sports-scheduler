<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules\CycleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;
use SportsScheduler\Schedules\CycleCreators\Helpers\Solutions4N;
use SportsScheduler\Schedules\CycleCreators\Helpers\Solutions4NPlus2;
use SportsScheduler\Schedules\CycleCreators\Helpers\Solutions4NPlus3;

class CycleCreatorAgainstTwoVsTwo extends CycleCreatorAgainstAbstract
{
//    protected int $highestGameRoundNumberCompleted = 0;
//    protected int $nrOfGamesPerGameRound = 0;
//    protected \DateTimeImmutable|null $timeoutDateTime = null;
//    protected int $tmpHighest = 0;
//
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createRootCycleAndGames(
        ScheduleAgainstTwoVsTwo $scheduleAgainstTwoVsTwo
    ): ScheduleCycleAgainstTwoVsTwo
    {
        $cycle = new ScheduleCycleAgainstTwoVsTwo($scheduleAgainstTwoVsTwo);

        for ($cycleNr = 1; $cycleNr <= $scheduleAgainstTwoVsTwo->nrOfCycles; $cycleNr++) {

            $this->createGames($cycle, $scheduleAgainstTwoVsTwo->scheduleWithNrOfPlaces->nrOfPlaces);
            if( $cycleNr < $scheduleAgainstTwoVsTwo->nrOfCycles ) {
                $cycle = $cycle->createNext();
            }

        }
        return $cycle->getFirst();
    }

    private function createGames(ScheduleCycleAgainstTwoVsTwo $cycle, int $nrOfPlaces): void {

        $rootCyclePart = $cycle->firstPart;
//        $swapHomeAways = ($cycle->getNumber() % 2) === 0;

        if( $nrOfPlaces % 4 === 0 ) {
            $this->create4NGames($rootCyclePart);
        } else if( $nrOfPlaces % 4 === 1 ) {
            $this->create4NPlus1Games($rootCyclePart);
        } else if( $nrOfPlaces % 4 === 2 ) {
            $this->create4NPlus2Games($rootCyclePart);
        } else if( $nrOfPlaces % 4 === 3 ) {
            $this->create4NPlus3Games($rootCyclePart);
        }
    }

    // 8 seats solution
    //
    // Table    North  South    East  West
    //    1        2      3        4     6
    //    2        5      1        7     0
    //
    // Round placNr placeNr placeNr placeNr placeNr placeNr placeNr placeNr         venue 1         venue 2
    // partNr     1       2       3       4       5       6       7       8
    // ---      ---     ---     ---     ---     ---     ---     ---     ---
    // 1          0       1       2       3       4       5       6       7     3 & 4 vs 5 & 7      6 & 2 vs 8 & 1
    // 2          0       2       3       4       5       6       7       1     etc
    // 3          0       3       4       5       6       7       1       2     etc
    // 4          0       4       5       6       7       1       2       3     etc
    // 5          0       5       6       7       1       2       3       4     etc
    // 6          0       6       7       1       2       3       4       5     etc
    // 7          0       7       1       2       3       4       5       6     4 & 5 vs 6 & 8      7 & 3 vs 2 & 1
    private function create4NGames(ScheduleCyclePartAgainstTwoVsTwo $cyclePart): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;

//        $swapHomeAways = ($cycle->getNumber() % 2) === 0;

        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        $nrOfCycleParts = $nrOfPlaces - 1;
        for ( $cyclePartNr = 1; $cyclePartNr <= $nrOfCycleParts; $cyclePartNr++ ) {
            $homeAways =  Solutions4N::create($placeNrs);

            foreach( $homeAways as $homeAway) {
                $cyclePart->addGame(new ScheduleGameAgainstTwoVsTwo($cyclePart, $homeAway));
            }

            // remove placeNr1 from front of list
            $placeNr1 = array_shift($placeNrs);
            if( $placeNr1 === null ) {
                throw new \Exception('should be a placeNr');
            }

            // move last placeNr from back to front of list
            $lastPlaceNr = array_pop($placeNrs);
            if( $lastPlaceNr === null ) {
                throw new \Exception('should be a placeNr');
            }
            array_unshift($placeNrs, $lastPlaceNr);
            // restore begin of list with $placeNr1
            array_unshift($placeNrs, $placeNr1);

            if( $cyclePartNr < $nrOfCycleParts ) {
                $cyclePart = $cyclePart->createNext();
            }
        }
    }

    private function create4NPlus1Games(ScheduleCyclePartAgainstTwoVsTwo $cyclePart): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;

//        $swapHomeAways = ($cycle->getNumber() % 2) === 0;

        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        $nrOfCycleParts = $nrOfPlaces;
        for ( $cyclePartNr = 1; $cyclePartNr <= $nrOfCycleParts; $cyclePartNr++ ) {
            $lastPlaceNr = array_pop($placeNrs);
            if( $lastPlaceNr === null ) {
                throw new \Exception('should be a placeNr');
            }
            $homeAways = Solutions4N::create($placeNrs);

            foreach( $homeAways as $homeAway) {
                $cyclePart->addGame(new ScheduleGameAgainstTwoVsTwo($cyclePart, $homeAway));
            }

            array_unshift($placeNrs, $lastPlaceNr);

            if( $cyclePartNr < $nrOfCycleParts ) {
                $cyclePart = $cyclePart->createNext();
            }
        }
    }

    private function create4NPlus2Games(ScheduleCyclePartAgainstTwoVsTwo $cyclePart): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;

//        $swapHomeAways = ($cycle->getNumber() % 2) === 0;

        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        $homeAways = Solutions4NPlus2::create($placeNrs);

        foreach( $homeAways as  $cyclePartNr => $cyclePartHomeAways) {
            if ($cyclePartNr !== $cyclePart->getNumber()) {
                $cyclePart = $cyclePart->createNext();
            }
            foreach ($cyclePartHomeAways as $homeAway) {
                $cyclePart->addGame(new ScheduleGameAgainstTwoVsTwo($cyclePart, $homeAway));
            }
        }
    }

    private function create4NPlus3Games(ScheduleCyclePartAgainstTwoVsTwo $cyclePart): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;

//        $swapHomeAways = ($cycle->getNumber() % 2) === 0;

        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        $homeAways = Solutions4NPlus3::create($placeNrs);

        foreach( $homeAways as  $cyclePartNr => $cyclePartHomeAways) {
            if ($cyclePartNr !== $cyclePart->getNumber()) {
                $cyclePart = $cyclePart->createNext();
            }
            foreach ($cyclePartHomeAways as $homeAway) {
                $cyclePart->addGame(new ScheduleGameAgainstTwoVsTwo($cyclePart, $homeAway));
            }
        }
    }


//    public function createRootAndDescendants(
//        AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces,
//        GppHomeAwayGenerator $homeAwayCreator,
//        AllScheduleMaps $allScheduleMaps/*,
//        AmountRange $amountRange,
//        AmountRange $againstAmountRange,
//        AmountRange $withAmountRange,
//        AmountRange $homeAmountRange,
//        int|null $nrOfSecondsBeforeTimeout*/
//    ): AgainstGameRound {
////        if( $nrOfSecondsBeforeTimeout > 0 ) {
////            $this->timeoutDateTime = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $nrOfSecondsBeforeTimeout . 'S'));
////        }
//        $gameRound = new AgainstGameRound($againstGppWithNrOfPlaces->getNrOfPlaces());
//        $this->highestGameRoundNumberCompleted = 0;
//        $this->nrOfGamesPerGameRound = $againstGppWithNrOfPlaces->getNrOfGamesSimultaneously();
//
//        $homeAways = $this->createHomeAways($homeAwayCreator, $againstGppWithNrOfPlaces);
//        $homeAways = $this->initHomeAways($homeAways);
//
////        $calculator = new EquallyAssignCalculator();
////        if( $calculator->assignAgainstSportsEqually( count($poule->getPlaces()), [$againstGpp] ) ) {
////
////        }
////        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getAmountCounterMap(),$amountRange);
////        $rangedWithNrCounterMap = new RangedDuoPlaceNrCounterMap($allScheduleMaps->getWithCounterMap(),$withAmountRange);
////        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($allScheduleMaps->getAgainstCounterMap(),$againstAmountRange);
////        $rangedHomeNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getHomeCounterMap(), $homeAmountRange);
////        $rangedAwayNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getAwayCounterMap(), $homeAmountRange);
////
////        $statisticsCalculator = new AgainstGppStatisticsCalculator(
////            $againstGppWithNrOfPlaces,
////            $rangedHomeNrCounterMap,
////            0,
////            $rangedAmountNrCounterMap,
////            $rangedAgainstNrCounterMap,
////            $rangedWithNrCounterMap,
////            $this->logger
////        );
//
////        $statisticsCalculator->output(true);
////        $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');
//
////        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS BEFORE SORT');
////        $homeAways = $statisticsCalculator->sortHomeAways($homeAways);
////        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS AFTER SORT');
//        if ($this->assignGameRound(
//                $againstGppWithNrOfPlaces,
//                $homeAways,
//                $homeAways,
//                // $statisticsCalculator,
//                $gameRound
//            ) === false) {
//            throw new NoSolutionException('gameRounds could not created, all possibilities tried', E_ERROR);
//        }
////        $homeAwayBalancer = new HomeAwayBalancer($this->logger);
////
////
////        $homeNrCounterMapCopy = $rangedHomeNrCounterMap->cloneAsSideNrCounterMap();
////
////        $awayNrCounterMapCopy = $rangedAwayNrCounterMap->cloneAsSideNrCounterMap();
////
////        $swappedHomeAways = $homeAwayBalancer->balance2(
////            $homeNrCounterMapCopy,
////            $rangedHomeNrCounterMap->getAllowedRange(),
////            $awayNrCounterMapCopy,
////            $gameRound->getAllHomeAways()
////        );
////        $this->updateWithSwappedHomeAways($gameRound, $swappedHomeAways);
//        return $gameRound;
//    }
//
//    /**
//     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAwaysForGameRound
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @param AgainstGameRound $gameRound
//     * @param int $depth
//     * @return bool
//     */
//    protected function assignGameRound(
//        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
//        array $homeAwaysForGameRound,
//        array $homeAways,
//        // AgainstGppStatisticsCalculator $statisticsCalculator,
//        AgainstGameRound $gameRound,
//        int $depth = 0
//    ): bool {
//        if( $againstWithNrOfPlaces->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAssigned() ) {
////            $statisticsCalculator->output(false);
////            $this->gameRoundOutput->outputHomeAways($gameRound->getAllHomeAways(), null, 'SUC AFTER SPORT');
//            if( $statisticsCalculator->allAssigned() ) {
//                return true;
//            }
//            return false;
//        }
//
//        if ($this->timeoutDateTime !== null && (new DateTimeImmutable()) > $this->timeoutDateTime) {
//            throw new TimeoutException('exceeded maximum duration', E_ERROR);
//        }
//
//        if ($this->isGameRoundCompleted($againstWithNrOfPlaces, $gameRound)) {
//            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
//
////            if (!$statisticsCalculator->minimalSportCanStillBeAssigned()) {
////                return false;
////            }
//
////            if (!$statisticsCalculator->minimalAgainstCanStillBeAssigned(null)) {
////                return false;
////            }
////            if (!$statisticsCalculator->minimalWithCanStillBeAssigned(null)) {
////                return false;
////            }
//
//
////            if( $this->highestGameRoundNumberCompleted > 5 ) {
////
////                // alle homeaways die over
////                $statisticsCalculator->output(false);
//
//                $filteredHomeAways = $statisticsCalculator->filterBeforeGameRound($homeAways);
////                    $filteredHomeAways = $homeAways;
//
////
////
////            } else {
////                $filteredHomeAways = $homeAways;
////            }
//
////            if( count($filteredHomeAways) === 0 ) {
////                return false;
////            }
//            if ($gameRound->getNumber() > $this->highestGameRoundNumberCompleted) {
//                $this->highestGameRoundNumberCompleted = $gameRound->getNumber();
////                 $this->logger->info('highestGameRoundNumberCompleted: ' . $gameRound->getNumber());
////
////                if( $this->highestGameRoundNumberCompleted === 9 ) {
////                    $statisticsCalculator->output(false);
////                    $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) . ' )');
////                    $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');
//////                    $this->gameRoundOutput->outputHomeAways($filteredHomeAways, null, 'HOMEAWAYS TO ASSIGN');
////                }
//
//                $filteredHomeAways = $statisticsCalculator->sortHomeAways($filteredHomeAways);
//            }
//            // $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) .  ' )');
//
//
//            $nrOfGamesToGo = $againstWithNrOfPlaces->getTotalNrOfGames() - $statisticsCalculator->getNrOfHomeAwaysAssigned();
//            if( count($filteredHomeAways) < $nrOfGamesToGo ) {
//                return false;
//            }
//            if( $this->assignGameRound(
//                $againstWithNrOfPlaces,
//                $filteredHomeAways,
//                $homeAways,
//                $statisticsCalculator,
//                $nextGameRound,
//                $depth + 1
//            ) ) {
//                return true;
//            }
////            else {
////                if( $gameRound->getNumber() <= 5 ) {
////                    $this->logger->info('return to gr  : ' . $gameRound->getNumber() );
////                }
////            }
//        }
//        // $this->logger->info('gr ' . $gameRound->getNumber() . ' trying.. ( grgames ' . count($gameRound->getHomeAways()) . ', haGr ' . count($homeAwaysForGameRound) .  ' )');
//
//        return $this->assignSingleGameRound(
//            $againstWithNrOfPlaces,
//            $homeAwaysForGameRound,
//            $homeAways,
//            $statisticsCalculator,
//            $gameRound,
//            $depth + 1
//        );
//    }
//
//    /**
//     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAwaysForGameRound
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @param AgainstGppStatisticsCalculator $statisticsCalculator,
//     * @param AgainstGameRound $gameRound
//     * @param int $depth
//     * @return bool
//     */
//    protected function assignSingleGameRound(
//        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
//        array $homeAwaysForGameRound,
//        array $homeAways,
//        AgainstGppStatisticsCalculator $statisticsCalculator,
//        AgainstGameRound $gameRound,
//        int $depth = 0
//    ): bool {
//
//        $triedHomeAways = [];
//        while($homeAway = array_shift($homeAwaysForGameRound)) {
//
//            if (!$statisticsCalculator->isHomeAwayAssignable($homeAway)) {
//                array_push($triedHomeAways, $homeAway);
//                continue;
//            }
//
//            $gameRound->add($homeAway);
//
//            $homeAwaysForGameRoundTmp = array_values(
//                array_filter(
//                    array_merge( $homeAwaysForGameRound, $triedHomeAways),
//                    function (OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use ($gameRound): bool {
//                        return !$gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway);
//                    }
//                )
//            );
//
//            if ((count($homeAwaysForGameRoundTmp) >= ($this->nrOfGamesPerGameRound - count($gameRound->getHomeAways()))
//                || $statisticsCalculator->getNrOfGamesToGo() === count($gameRound->getHomeAways())
//                )
//                && $this->assignGameRound(
//                    $againstWithNrOfPlaces,
//                    $homeAwaysForGameRoundTmp,
//                    $homeAways,
//                    $statisticsCalculator->addHomeAway($homeAway),
//                    $gameRound,
//                    $depth + 1
//            )) {
//                return true;
//            }
//            $this->releaseHomeAway($gameRound, $homeAway);
//            array_push($triedHomeAways, $homeAway);
//
//        }
//        return false;
//    }
//
//    /**
//     * @param GppHomeAwayGenerator $homeAwayCreator
//     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
//     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
//     */
//    protected function createHomeAways(
//        GppHomeAwayGenerator $homeAwayCreator,
//        AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
//    {
//        $totalNrOfGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();
//        $homeAways = [];
//        while ( count($homeAways) < $totalNrOfGames ) {
//            $homeAways = array_merge($homeAways, $homeAwayCreator->create($againstGppWithNrOfPlaces));
//        }
//        return $homeAways;
//    }
//
////    /**
////     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
////     * @param int $currentGameRoundNumber
////     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
////     * @return bool
////     */
////    protected function isOverAssigned(
////        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
////        int $currentGameRoundNumber,
////        array $homeAways
////    ): bool {
////        $unassignedMap = new AmountNrCounterMap($againstWithNrOfPlaces->getNrOfPlaces());
////        $unassignedMap->addHomeAways($homeAways);
////
////        $nrOfGamePlacesPerBatch = $againstWithNrOfPlaces->getNrOfGamePlacesPerBatch();
////        $placeNrs = (new SportRange(1, $againstWithNrOfPlaces->getNrOfPlaces()))->toArray();
////        foreach ($placeNrs as $placeNr) {
////            if ($currentGameRoundNumber + $unassignedMap->count($placeNr) > $nrOfGamePlacesPerBatch) {
////                return true;
////            }
////        }
////        return false;
////    }
//
//    /**
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
//     */
//    private function initHomeAways(array $homeAways): array {
//        /** @var list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $newHomeAways */
//        $newHomeAways = [];
//        while( $homeAway = array_shift($homeAways) ) {
//            if( (count($homeAways) % 2) === 0 ) {
//                array_unshift($newHomeAways, $homeAway);
//            } else {
//                $newHomeAways[] = $homeAway;
//            }
//        }
//
////        while( count($homeAways) > 0 ) {
////            if( (count($homeAways) % 2) === 0 ) {
////                $homeAway = array_shift($homeAways);
////            } else {
////                $homeAway = array_pop($homeAways);
////            }
////            array_push($newHomeAways, $homeAway);
////        }
//
//        return $newHomeAways;
//    }
//
//    /**
//     * @param AgainstGameRound $gameRound
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $swappedHomeAways
//     * @return void
//     */
//    protected function updateWithSwappedHomeAways(AgainstGameRound $gameRound, array $swappedHomeAways): void {
//        foreach( $swappedHomeAways as $swappedHomeAway ) {
//            $gameRoundIt = $gameRound;
//            while($gameRoundIt && !$gameRoundIt->swapSidesOfHomeAway($swappedHomeAway)) {
//                $gameRoundIt = $gameRoundIt->getNext();
//            }
//        }
//    }
}
