<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsScheduler\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsScheduler\GameRound\Creator\AgainstGameRoundCreatorAbstract as AgainstCreator;

class AgainstH2hGameRoundCreator extends AgainstCreator
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        int $nrOfPlaces,
        AgainstH2h $sportVariant,
        H2hHomeAwayCreator $homeAwayCreator,
        SideNrCounterMap $homeNrCounterMap,
        AmountRange $homeAmountRange
    ): AgainstGameRound {
        // $againstH2hWithPoule = new AgainstH2hWithPoule($poule, $sportVariant);
        $gameRound = new AgainstGameRound();
        $homeAways = $homeAwayCreator->createForOneH2H($nrOfPlaces);

        $statisticsCalculator = new H2hStatisticsCalculator(
            $againstH2hWithPoule,
            new RangedPlaceCounterMap($homeNrCounterMap, $homeAmountRange),
            0,
            new PlaceCounterMap($mapper->initPlaceCounterMap($poule)),
            $this->logger
        );

        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $againstH2hWithPoule,
                $homeAwayCreator,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    /**
     * @param AgainstH2hWithPoule $againstWithPoule
     * @param H2hHomeAwayCreator $homeAwayCreator
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param H2hStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        AgainstH2hWithPoule $againstWithPoule,
        H2hHomeAwayCreator $homeAwayCreator,
        array $homeAwaysForGameRound,
        array $homeAways,
        H2hStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
        if ($statisticsCalculator->allAssigned()) {
            return true;
        }

        if ($this->isGameRoundCompleted($againstWithPoule, $gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $homeAways = $homeAwayCreator->createForOneH2H($againstWithPoule);
            }

//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->output($gameRound);
//                $this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($homeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }


            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
            //                $this->gameRoundOutput->output($gameRound);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
            $nextHomeAways = $homeAways;
//
//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->outputHomeAways($sortedHomeAways, $gameRound, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
//            }

//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            // $gamesList = array_values($gamesForBatchTmp);
//            shuffle($homeAways);
            return $this->assignGameRound(
                $againstWithPoule,
                $homeAwayCreator,
                $nextHomeAways,
                $homeAways,
                $statisticsCalculator,
                $nextGameRound
            );
        }

        if ($nrOfHomeAwaysTried === count($homeAwaysForGameRound)) {
            return false;
        }
        $homeAway = array_shift($homeAwaysForGameRound);
        if ($homeAway === null) {
            return false;
        }

        if ($this->isHomeAwayAssignable($gameRound, $homeAway)) {

            $gameRound->add($homeAway);
            $statisticsCalculatorTry = $statisticsCalculator->addHomeAway($homeAway);

//            if ($gameRound->getNumber() === 15 ) {
//                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround 15');
//                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround 15');
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }
            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    $homeAwaysForGameRound,
                    function (HomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );
            if ($this->assignGameRound(
                $againstWithPoule,
                $homeAwayCreator,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $statisticsCalculatorTry,
                $gameRound
            )) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
        }
        $homeAwaysForGameRound[] = $homeAway;
        ++$nrOfHomeAwaysTried;
        return $this->assignGameRound(
            $againstWithPoule,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }


    protected function isHomeAwayAssignable(AgainstGameRound $gameRound, HomeAway $homeAway): bool {
        foreach ($homeAway->getPlaces() as $place) {
            if ($gameRound->isParticipating($place) ) {
                return false;
            }
        }
        return true;
    }
}