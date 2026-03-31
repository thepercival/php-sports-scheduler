<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\GameRoundCreators;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\Amounts\AmountRange;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCounter;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCounterMap;
use SportsPlanning\Schedules\GameRounds\ScheduleAgainstGameRound;
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;
use SportsScheduler\Combinations\HomeAwayBalancer;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstGppHomeAwayCreator as GppHomeAwayCreator;
use SportsScheduler\Combinations\StatisticsCalculators\AgainstGppStatisticsCalculator;
use SportsScheduler\Exceptions\NoSolutionException;
use SportsScheduler\Exceptions\TimeoutException;

final class ScheduleAgainstGppGameRoundCreator extends ScheduleAgainstGameRoundCreatorAbstract
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
        AssignedCounter $assignedCounter,
        AmountRange $amountRange,
        AmountRange $againstAmountRange,
        AmountRange $withAmountRange,
        AmountRange $homeAmountRange,
        int|null $nrOfSecondsBeforeTimeout
    ): ScheduleAgainstGameRound {
        if( $nrOfSecondsBeforeTimeout > 0 ) {
            $this->timeoutDateTime = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $nrOfSecondsBeforeTimeout . 'S'));
        }
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
        $gameRound = new ScheduleAgainstGameRound();
        $this->highestGameRoundNumberCompleted = 0;
        $this->nrOfGamesPerGameRound = $variantWithNrOfPlaces->getNrOfGamesSimultaneously();

        $homeAways = $this->createHomeAways($homeAwayCreator, $nrOfPlaces, $againstGpp);
        $homeAways = $this->initHomeAways($homeAways);

        $assignedMap = new RangedPlaceNrCounterMap(
            $assignedCounter->getAssignedMap(),
            $amountRange
        );
        $assignedAgainstMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedAgainstMap(),
            $againstAmountRange
        );
        $assignedWithMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedWithMap(),
            $withAmountRange
        );
//        $assignedCounter->getAssignedWithMap()->output($this->logger, '', '');
        $assignedHomeMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedHomeMap(), $homeAmountRange
        );

        $statisticsCalculator = new AgainstGppStatisticsCalculator(
            $variantWithNrOfPlaces,
            $assignedHomeMap,
            0,
            $assignedMap,
            $assignedAgainstMap,
            $assignedWithMap,
            $this->logger
        );

//        $statisticsCalculator->output(true);
//        $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');

//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS BEFORE SORT');
        $homeAways = $statisticsCalculator->sortHomeAways($homeAways, $this->logger);
//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS AFTER SORT');
        if ($this->assignGameRound(
                $variantWithNrOfPlaces,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new NoSolutionException('creation of homeaway can not be false', E_ERROR);
        }
        $homeAwayBalancer = new HomeAwayBalancer();
        $swappedHomeAways = $homeAwayBalancer->balance2(
            $assignedHomeMap,
            $assignedCounter->getAssignedAwayMap(),
            $gameRound->getAllHomeAways()
        );
        $this->updateWithSwappedHomeAways($gameRound, $swappedHomeAways);
        return $gameRound;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param AgainstGppStatisticsCalculator $statisticsCalculator
     * @param ScheduleAgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        array $homeAwaysForGameRound,
        array $homeAways,
        AgainstGppStatisticsCalculator $statisticsCalculator,
        ScheduleAgainstGameRound $gameRound,
        int $depth = 0
    ): bool {
        if( $againstWithNrOfPlaces->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAssigned() ) {
//            $statisticsCalculator->output(false);
//            $this->gameRoundOutput->outputHomeAways($gameRound->getAllHomeAways(), null, 'SUC AFTER SPORT');
            if( $statisticsCalculator->allAssigned() ) {
                return true;
            }
            return false;
        }

        if ($this->timeoutDateTime !== null && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException('exceeded maximum duration', E_ERROR, null);
        }

        if ($this->isGameRoundCompleted($againstWithNrOfPlaces, $gameRound)) {
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


            $nrOfGamesToGo = $againstWithNrOfPlaces->getTotalNrOfGames() - $statisticsCalculator->getNrOfHomeAwaysAssigned();
            if( count($filteredHomeAways) < $nrOfGamesToGo ) {
                return false;
            }
            if( $this->assignGameRound(
                $againstWithNrOfPlaces,
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
            $againstWithNrOfPlaces,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $depth + 1
        );
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param AgainstGppStatisticsCalculator $statisticsCalculator,
     * @param ScheduleAgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        array $homeAwaysForGameRound,
        array $homeAways,
        AgainstGppStatisticsCalculator $statisticsCalculator,
        ScheduleAgainstGameRound $gameRound,
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
                    $againstWithNrOfPlaces,
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
     * @param int $nrOfPlaces
     * @param AgainstGpp $sportVariant
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        int $nrOfPlaces,
        AgainstGpp $sportVariant): array
    {
        $variantWithNrOfPlaces = (new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant));
        $totalNrOfGames = $variantWithNrOfPlaces->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($variantWithNrOfPlaces));
        }
        return $homeAways;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
     * @param int $currentGameRoundNumber
     * @param list<HomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
        $nrOfPlaces = $againstWithNrOfPlaces->getNrOfPlaces();
        $unassignedMap = [];
        for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
            $unassignedMap[$placeNr] = new PlaceNrCounter($placeNr);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaceNrs() as $placeNr) {
                $unassignedMap[$placeNr]->increment();
            }
        }

        $nrOfGamePlacesPerBatch = $againstWithNrOfPlaces->getNrOfGamePlacesPerBatch();
        for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
            if ($currentGameRoundNumber + $unassignedMap[$placeNr]->count() > $nrOfGamePlacesPerBatch) {
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
     * @param ScheduleAgainstGameRound $gameRound
     * @param list<HomeAway> $swappedHomeAways
     * @return void
     */
    protected function updateWithSwappedHomeAways(ScheduleAgainstGameRound $gameRound, array $swappedHomeAways): void {
        foreach( $swappedHomeAways as $swappedHomeAway ) {
            $gameRoundIt = $gameRound;
            while($gameRoundIt && !$gameRoundIt->swapSidesOfHomeAway($swappedHomeAway)) {
                $gameRoundIt = $gameRoundIt->getNext();
            }
        }
    }
}
