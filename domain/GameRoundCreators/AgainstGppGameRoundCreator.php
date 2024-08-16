<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\RangedDuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsScheduler\Combinations\HomeAwayBalancer;
use SportsScheduler\Combinations\HomeAwayGenerators\GppHomeAwayGenerator as GppHomeAwayCreator;
use SportsScheduler\Combinations\AgainstStatisticsCalculators\AgainstGppStatisticsCalculator;
use SportsScheduler\Exceptions\NoSolutionException;
use SportsScheduler\Exceptions\TimeoutException;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;

class AgainstGppGameRoundCreator extends AgainstGameRoundCreatorAbstract
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
        AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces,
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
        $gameRound = new AgainstGameRound();
        $this->highestGameRoundNumberCompleted = 0;
        $this->nrOfGamesPerGameRound = $againstGppWithNrOfPlaces->getNrOfGamesSimultaneously();

        $homeAways = $this->createHomeAways($homeAwayCreator, $againstGppWithNrOfPlaces);
        $homeAways = $this->initHomeAways($homeAways);

//        $calculator = new EquallyAssignCalculator();
//        if( $calculator->assignAgainstSportsEqually( count($poule->getPlaces()), [$againstGpp] ) ) {
//
//        }
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getAmountCounterMap(),$amountRange);
        $rangedWithNrCounterMap = new RangedDuoPlaceNrCounterMap($allScheduleMaps->getWithCounterMap(),$withAmountRange);
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($allScheduleMaps->getAgainstCounterMap(),$againstAmountRange);
        $rangedHomeNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getHomeCounterMap(), $homeAmountRange);
        $rangedAwayNrCounterMap = new RangedPlaceNrCounterMap($allScheduleMaps->getAwayCounterMap(), $homeAmountRange);

        $statisticsCalculator = new AgainstGppStatisticsCalculator(
            $againstGppWithNrOfPlaces,
            $rangedHomeNrCounterMap,
            0,
            $rangedAmountNrCounterMap,
            $rangedAgainstNrCounterMap,
            $rangedWithNrCounterMap,
            $this->logger
        );

//        $statisticsCalculator->output(true);
//        $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');

//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS BEFORE SORT');
        $homeAways = $statisticsCalculator->sortHomeAways($homeAways);
//        $this->gameRoundOutput->outputHomeAways($homeAways, null, 'UNASSIGNED HOMEAWAYS AFTER SORT');
        if ($this->assignGameRound(
                $againstGppWithNrOfPlaces,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new NoSolutionException('gameRounds could not created, all possibilities tried', E_ERROR);
        }
        $homeAwayBalancer = new HomeAwayBalancer($this->logger);


        $homeNrCounterMapCopy = $rangedHomeNrCounterMap->cloneAsSideNrCounterMap();

        $awayNrCounterMapCopy = $rangedAwayNrCounterMap->cloneAsSideNrCounterMap();

        $swappedHomeAways = $homeAwayBalancer->balance2(
            $homeNrCounterMapCopy,
            $rangedHomeNrCounterMap->getAllowedRange(),
            $awayNrCounterMapCopy,
            $gameRound->getAllHomeAways()
        );
        $this->updateWithSwappedHomeAways($gameRound, $swappedHomeAways);
        return $gameRound;
    }

    /**
     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAwaysForGameRound
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param AgainstGppStatisticsCalculator $statisticsCalculator
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        array $homeAwaysForGameRound,
        array $homeAways,
        AgainstGppStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
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
            throw new TimeoutException('exceeded maximum duration', E_ERROR);
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

                $filteredHomeAways = $statisticsCalculator->sortHomeAways($filteredHomeAways);
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
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAwaysForGameRound
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param AgainstGppStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
        array $homeAwaysForGameRound,
        array $homeAways,
        AgainstGppStatisticsCalculator $statisticsCalculator,
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
                    function (OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway);
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
     * @param AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
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

//    /**
//     * @param AgainstGppWithNrOfPlaces $againstWithNrOfPlaces
//     * @param int $currentGameRoundNumber
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @return bool
//     */
//    protected function isOverAssigned(
//        AgainstGppWithNrOfPlaces $againstWithNrOfPlaces,
//        int $currentGameRoundNumber,
//        array $homeAways
//    ): bool {
//        $unassignedMap = new AmountNrCounterMap($againstWithNrOfPlaces->getNrOfPlaces());
//        $unassignedMap->addHomeAways($homeAways);
//
//        $nrOfGamePlacesPerBatch = $againstWithNrOfPlaces->getNrOfGamePlacesPerBatch();
//        $placeNrs = (new SportRange(1, $againstWithNrOfPlaces->getNrOfPlaces()))->toArray();
//        foreach ($placeNrs as $placeNr) {
//            if ($currentGameRoundNumber + $unassignedMap->count($placeNr) > $nrOfGamePlacesPerBatch) {
//                return true;
//            }
//        }
//        return false;
//    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    private function initHomeAways(array $homeAways): array {
        /** @var list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $newHomeAways */
        $newHomeAways = [];
        while( $homeAway = array_shift($homeAways) ) {
            if( (count($homeAways) % 2) === 0 ) {
                array_unshift($newHomeAways, $homeAway);
            } else {
                $newHomeAways[] = $homeAway;
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
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $swappedHomeAways
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
