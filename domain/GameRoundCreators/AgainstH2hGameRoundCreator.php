<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsScheduler\Combinations\AgainstStatisticsCalculators\AgainstH2hStatisticsCalculator;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsScheduler\Combinations\HomeAwayGenerators\H2hHomeAwayGenerator;

class AgainstH2hGameRoundCreator extends AgainstGameRoundCreatorAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        int                  $nrOfPlaces,
        AgainstH2h           $againstH2h,
        H2hHomeAwayGenerator $homeAwayCreator,
        SideNrCounterMap     $homeNrCounterMap,
        AmountRange          $homeAmountRange
    ): AgainstGameRound {
        $againstH2hWithNrOfPlaces = new AgainstH2hWithNrOfPlaces($nrOfPlaces, $againstH2h);
        $gameRound = new AgainstGameRound();
        $homeAways = $homeAwayCreator->createForOneH2h($nrOfPlaces);

        $statisticsCalculator = new AgainstH2hStatisticsCalculator(
            $againstH2hWithNrOfPlaces,
            new RangedPlaceNrCounterMap($homeNrCounterMap, $homeAmountRange),
            0,
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
     * @param AgainstH2hWithNrOfPlaces $againstH2hWithNrOfPlaces
     * @param H2HHomeAwayGenerator $homeAwayCreator
     * @param list<OneVsOneHomeAway> $homeAwaysForGameRound
     * @param list<OneVsOneHomeAway> $homeAways
     * @param AgainstH2hStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        AgainstH2hWithNrOfPlaces       $againstH2hWithNrOfPlaces,
        H2HHomeAwayGenerator           $homeAwayCreator,
        array                          $homeAwaysForGameRound,
        array                          $homeAways,
        AgainstH2hStatisticsCalculator $statisticsCalculator,
        AgainstGameRound               $gameRound,
        int                            $nrOfHomeAwaysTried = 0
    ): bool {
        if ($statisticsCalculator->allAssigned()) {
            return true;
        }

        if ($this->isGameRoundCompleted($againstH2hWithNrOfPlaces, $gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $homeAways = $homeAwayCreator->createForOneH2h($againstH2hWithNrOfPlaces->getNrOfPlaces());
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
                $againstH2hWithNrOfPlaces,
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

        if ( $gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway) ) {

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
                    function (OneVsOneHomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway);
                    }
                )
            );
            if ($this->assignGameRound(
                $againstH2hWithNrOfPlaces,
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
            $againstH2hWithNrOfPlaces,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }
}
