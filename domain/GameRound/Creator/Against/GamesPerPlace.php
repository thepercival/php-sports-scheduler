<?php

declare(strict_types=1);

namespace SportsScheduler\GameRound\Creator\Against;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\CombinationMapper;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideCounterMap;
use SportsScheduler\Combinations\HomeAwayBalancer;
use SportsScheduler\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsScheduler\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsScheduler\Exceptions\NoSolutionException;
use SportsScheduler\Exceptions\TimeoutException;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsScheduler\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Poule;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;

class GamesPerPlace extends AgainstCreator
{
    protected int $highestGameRoundNumberCompleted = 0;
    protected int $nrOfGamesPerGameRound = 0;
    protected \DateTimeImmutable|null $timeoutDateTime = null;
    protected int $tmpHighest = 0;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        int $nrOfPlaces,
        AgainstGpp $againstGpp,
        GppHomeAwayCreator $homeAwayCreator,
        AllScheduleMaps $allScheduleMaps,
        AmountRange $amountRange,
        AmountRange $againstAmountRange,
        AmountRange $withAmountRange,
        AmountRange $homeAmountRange,
        int|null $nrOfSecondsBeforeTimeout
    ): AgainstGameRound {
        if( $nrOfSecondsBeforeTimeout > 0 ) {
            $this->timeoutDateTime = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $nrOfSecondsBeforeTimeout . 'S'));
        }
        $againstGppWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
        $gameRound = new AgainstGameRound();
        $this->highestGameRoundNumberCompleted = 0;
        $this->nrOfGamesPerGameRound = $againstGppWithNrOfPlaces->getNrOfGamesSimultaneously();

        $homeAways = $this->createHomeAways($homeAwayCreator, $nrOfPlaces, $againstGpp);
        $homeAways = $this->initHomeAways($homeAways);

//        $calculator = new EquallyAssignCalculator();
//        if( $calculator->assignAgainstSportsEqually( count($poule->getPlaces()), [$againstGpp] ) ) {
//
//        }
        $rangedAmountCounterMap = new RangedPlaceCounterMap($allScheduleMaps->getAmountCounterMap(),$amountRange);
        $rangedWithCounterMap = new RangedPlaceCombinationCounterMap($allScheduleMaps->getWithCounterMap(),$withAmountRange);
        $rangedAgainstCounterMap = new RangedPlaceCombinationCounterMap($allScheduleMaps->getAgainstCounterMap(),$againstAmountRange);
        $rangedHomeCounterMap = new RangedPlaceCounterMap($allScheduleMaps->getHomeCounterMap(), $homeAmountRange);
        $rangedAwayCounterMap = new RangedPlaceCounterMap($allScheduleMaps->getAwayCounterMap(), $homeAmountRange);

        $statisticsCalculator = new GppStatisticsCalculator(
            $againstGppWithNrOfPlaces,
            $rangedHomeCounterMap,
            0,
            $rangedAmountCounterMap,
            $rangedAgainstCounterMap,
            $rangedWithCounterMap,
            $this->logger
        );

//        $statisticsCalculator->output(true);
//        $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');

//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS BEFORE SORT');
        $homeAways = $statisticsCalculator->sortHomeAways($homeAways, $this->logger);
//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS AFTER SORT');
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new NoSolutionException('gameRounds could not created, all possibilities tried', E_ERROR);
        }
        $homeAwayBalancer = new HomeAwayBalancer($this->logger);

        $homeCounterMap = new SideCounterMap(Side::Home, $rangedHomeCounterMap->copyPlaceCounterMap());
        $awayCounterMap = new SideCounterMap(Side::Away, $rangedAwayCounterMap->copyPlaceCounterMap());
        $swappedHomeAways = $homeAwayBalancer->balance2(
            $homeCounterMap,
            $rangedHomeCounterMap->getAllowedRange(),
            $awayCounterMap,
            $gameRound->getAllHomeAways()
        );
        $this->updateWithSwappedHomeAways($gameRound, $swappedHomeAways);
        return $gameRound;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param GppStatisticsCalculator $statisticsCalculator
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        array $homeAwaysForGameRound,
        array $homeAways,
        GppStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {
        if( $againstWithPoule->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAssigned() ) {
//            $statisticsCalculator->output(false);
//            $this->gameRoundOutput->outputHomeAways($gameRound->getAllHomeAways(), null, 'SUC AFTER SPORT');
            if( $statisticsCalculator->allAssigned() ) {
                return true;
            }
            return false;
        }

        if ($this->timeoutDateTime !== null && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException('exceeded maximum duration', E_ERROR);
        }

        if ($this->isGameRoundCompleted($againstWithPoule, $gameRound)) {
            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);

//            if (!$statisticsCalculator->minimalSportCanStillBeAssigned()) {
//                return false;
//            }

//            if (!$statisticsCalculator->minimalAgainstCanStillBeAssigned(null)) {
//                return false;
//            }
//            if (!$statisticsCalculator->minimalWithCanStillBeAssigned(null)) {
//                return false;
//            }


//            if( $this->highestGameRoundNumberCompleted > 5 ) {
//
//                // alle homeaways die over
//                $statisticsCalculator->output(false);

                $filteredHomeAways = $statisticsCalculator->filterBeforeGameRound($homeAways);
//                    $filteredHomeAways = $homeAways;

//
//
//            } else {
//                $filteredHomeAways = $homeAways;
//            }

//            if( count($filteredHomeAways) === 0 ) {
//                return false;
//            }
            if ($gameRound->getNumber() > $this->highestGameRoundNumberCompleted) {
                $this->highestGameRoundNumberCompleted = $gameRound->getNumber();
//                 $this->logger->info('highestGameRoundNumberCompleted: ' . $gameRound->getNumber());
//
//                if( $this->highestGameRoundNumberCompleted === 9 ) {
//                    $statisticsCalculator->output(false);
//                    $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) . ' )');
//                    $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');
////                    $this->gameRoundOutput->outputHomeAways($filteredHomeAways, null, 'HOMEAWAYS TO ASSIGN');
//                }

                $filteredHomeAways = $statisticsCalculator->sortHomeAways($filteredHomeAways, $this->logger);
            }
            // $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) .  ' )');


            $nrOfGamesToGo = $againstWithPoule->getTotalNrOfGames() - $statisticsCalculator->getNrOfHomeAwaysAssigned();
            if( count($filteredHomeAways) < $nrOfGamesToGo ) {
                return false;
            }
            if( $this->assignGameRound(
                $againstWithPoule,
                $filteredHomeAways,
                $homeAways,
                $statisticsCalculator,
                $nextGameRound,
                $depth + 1
            ) ) {
                return true;
            }
//            else {
//                if( $gameRound->getNumber() <= 5 ) {
//                    $this->logger->info('return to gr  : ' . $gameRound->getNumber() );
//                }
//            }
        }
        // $this->logger->info('gr ' . $gameRound->getNumber() . ' trying.. ( grgames ' . count($gameRound->getHomeAways()) . ', haGr ' . count($homeAwaysForGameRound) .  ' )');

        return $this->assignSingleGameRound(
            $againstWithPoule,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $depth + 1
        );
    }

    /**
     * @param AgainstGppWithPoule $againstWithPoule
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param GppStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        AgainstGppWithPoule $againstWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        GppStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        $triedHomeAways = [];
        while($homeAway = array_shift($homeAwaysForGameRound)) {

            if (!$statisticsCalculator->isHomeAwayAssignable($homeAway)) {
                array_push($triedHomeAways, $homeAway);
                continue;
            }

            $gameRound->add($homeAway);

            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    array_merge( $homeAwaysForGameRound, $triedHomeAways),
                    function (HomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );

            if ((count($homeAwaysForGameRoundTmp) >= ($this->nrOfGamesPerGameRound - count($gameRound->getHomeAways()))
                || $statisticsCalculator->getNrOfGamesToGo() === count($gameRound->getHomeAways())
                )
                && $this->assignGameRound(
                    $againstWithPoule,
                    $homeAwaysForGameRoundTmp,
                    $homeAways,
                    $statisticsCalculator->addHomeAway($homeAway),
                    $gameRound,
                    $depth + 1
            )) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
            array_push($triedHomeAways, $homeAway);

        }
        return false;
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces): array
    {
        $totalNrOfGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($againstGppWithNrOfPlaces));
        }
        return $homeAways;
    }

    /**
     * @param AgainstGppWithPoule $againstWithPoule
     * @param int $currentGameRoundNumber
     * @param list<HomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        AgainstGppWithPoule $againstWithPoule,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
        $poule = $againstWithPoule->getPoule();
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getPlaceNr()] = new CounterForPlace($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedCounter = $unassignedMap[$place->getPlaceNr()];
                $unassignedMap[$place->getPlaceNr()] = $unassignedCounter->increment();
            }
        }

        $nrOfGamePlacesPerBatch = $againstWithPoule->getNrOfGamePlacesPerBatch();
        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getPlaceNr()]->count() > $nrOfGamePlacesPerBatch) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    private function initHomeAways(array $homeAways): array {
        /** @var list<HomeAway> $newHomeAways */
        $newHomeAways = [];
        while( $homeAway = array_shift($homeAways) ) {
            if( (count($homeAways) % 2) === 0 ) {
                array_unshift($newHomeAways, $homeAway);
            } else {
                array_push($newHomeAways, $homeAway);
            }
        }

//        while( count($homeAways) > 0 ) {
//            if( (count($homeAways) % 2) === 0 ) {
//                $homeAway = array_shift($homeAways);
//            } else {
//                $homeAway = array_pop($homeAways);
//            }
//            array_push($newHomeAways, $homeAway);
//        }

        return $newHomeAways;
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param list<HomeAway> $swappedHomeAways
     * @return void
     */
    protected function updateWithSwappedHomeAways(AgainstGameRound $gameRound, array $swappedHomeAways): void {
        foreach( $swappedHomeAways as $swappedHomeAway ) {
            $gameRoundIt = $gameRound;
            while($gameRoundIt && !$gameRoundIt->swapSidesOfHomeAway($swappedHomeAway)) {
                $gameRoundIt = $gameRoundIt->getNext();
            }
        }
    }
}
