<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\Amounts\AmountRange;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceNrCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;
use SportsPlanning\Schedules\GameRounds\ScheduleAgainstGameRound;
use SportsPlanning\SportVariant\AgainstH2hWithNrOfPlaces;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstH2hHomeAwayCreator;
use SportsScheduler\Combinations\StatisticsCalculators\AgainstH2hStatisticsCalculator;

final class ScheduleAgainstH2hGameRoundCreator extends ScheduleAgainstGameRoundCreatorAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        int $nrOfPlaces,
        AgainstH2h $sportVariant,
        AgainstH2hHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter,
        AmountRange $homeAmountRange
    ): ScheduleAgainstGameRound {
        $againstH2hWithNrOfPlaces = new AgainstH2hWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $mapper = new Mapper();
        $gameRound = new ScheduleAgainstGameRound();
        $homeAways = $homeAwayCreator->createForOneH2h($againstH2hWithNrOfPlaces);

        $statisticsCalculator = new AgainstH2hStatisticsCalculator(
            $againstH2hWithNrOfPlaces,
            new RangedPlaceNrCombinationCounterMap($assignedCounter->getAssignedHomeMap(), $homeAmountRange),
            0,
            new PlaceNrCounterMap( $mapper->getPlaceNrMap($nrOfPlaces) ),
            $this->logger
        );

        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $againstH2hWithNrOfPlaces,
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
     * @param AgainstH2hWithNrOfPlaces $againstWithNrOfPlaces
     * @param AgainstH2hHomeAwayCreator $homeAwayCreator
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param AgainstH2hStatisticsCalculator $statisticsCalculator,
     * @param ScheduleAgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        AgainstH2hWithNrOfPlaces $againstWithNrOfPlaces,
        AgainstH2hHomeAwayCreator $homeAwayCreator,
        array $homeAwaysForGameRound,
        array $homeAways,
        AgainstH2hStatisticsCalculator $statisticsCalculator,
        ScheduleAgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
        if ($statisticsCalculator->allAssigned()) {
            return true;
        }

        if ($this->isGameRoundCompleted($againstWithNrOfPlaces, $gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $homeAways = $homeAwayCreator->createForOneH2h($againstWithNrOfPlaces);
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
                $againstWithNrOfPlaces,
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
                $againstWithNrOfPlaces,
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
            $againstWithNrOfPlaces,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }


    protected function isHomeAwayAssignable(ScheduleAgainstGameRound $gameRound, HomeAway $homeAway): bool {
        foreach ($homeAway->getPlaceNrs() as $placeNr) {
            if ($gameRound->isParticipating($placeNr) ) {
                return false;
            }
        }
        return true;
    }
}
