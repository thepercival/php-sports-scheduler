<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;

class AgainstH2hGameRoundCreator extends AgainstGameRoundCreatorAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createRootAndDescendants(
        int                  $nrOfPlaces,
        AgainstH2h           $againstH2h,
    ): AgainstGameRound
    {
        $gameRound = new AgainstGameRound($nrOfPlaces);
        for ($h2hNr = 1; $h2hNr <= $againstH2h->nrOfH2h; $h2hNr++) {
            $gameRound = $this->assignHomeAways($gameRound, $h2hNr);
        }
        return $gameRound->getFirst();
    }

//        $statisticsCalculator = new AgainstH2hStatisticsCalculator(
//            $againstH2hWithNrOfPlaces,
//            new RangedPlaceNrCounterMap($homeNrCounterMap, $homeAmountRange),
//            0,
//            $this->logger
//        );
//
//        // $this->outputUnassignedHomeAways($homeAways);
//        if ($this->assignGameRound(
//                $againstH2hWithNrOfPlaces,
//                $homeAwayCreator,
//                $homeAways,
//                $homeAways,
//                $gameRound,
//                ++$currentNrOfH2h,
//            ) === false) {
//            throw new \Exception('creation of homeaway can not be false', E_ERROR);
//        }
//        return $gameRound;
//    }


    private function assignHomeAways(AgainstGameRound $gameRound, int $h2hNr): AgainstGameRound {

        $swapHomeAways = ($h2hNr % 2) === 0;
        if( $gameRound->nrOfPlaces % 2 === 0 ) {
            return $this->assignHomeAways2N($gameRound, $swapHomeAways);
        }
        return $this->assignHomeAways2NPlus1($gameRound, $swapHomeAways);
    }

    // 4 seats          : 0 vs 3 & 1 vs 2
    // 4 placeNrs       : 1, 2, 3, 4
    // placeNr 1 stays at seat 0, other placeNrs go to the next seatNumber
    // roundNumber              games                   placeNrs
    // seats            : 0 vs 3 & 1 vs 2              1 2 3 4
    // 1                  1 vs 4 & 2 vs 3        seat  0 1 2 3
    // 2                  1 vs 3 & 4 vs 2              0 2 3 1
    // 3                  1 vs 2 & 3 vs 4              0 3 1 2
    //
    // 6 seats          : 0 vs 5 & 1 vs 4 & 2 vs 3
    // 6 placeNrs       : 1, 2, 3, 4, 5, 6
    // placeNr 1 stays at seat 0, other placeNrs go to the next seatNumber
    // roundNrs (N-1)          games                        placeNrs
    // seats            : 0 vs 5 & 1 vs 4 & 2 vs 3          1 2 3 4 5 6
    // 1                  1 vs 6 & 2 vs 5 & 3 vs 4    seat  0 1 2 3 4 5
    // 2                  1 vs 5 & 6 vs 4 & 2 vs 3    seat  0 2 3 4 5 1
    // 1                  1 vs 4 & 5 vs 3 & 6 vs 2    seat  0 3 4 5 1 2
    // 1                  1 vs 3 & 4 vs 2 & 5 vs 6    seat  0 4 5 1 2 3
    // 1                  1 vs 2 & 3 vs 6 & 4 vs 5    seat  0 5 1 2 3 4
    //
    // 1 vs 1 : (placeNrOne + placeNrTwo) % 2) is 0 means swap
    private function assignHomeAways2N(AgainstGameRound $gameRound, bool $swapHomeAways): AgainstGameRound {

        $homeAwaySeats = [];
        {
            $seatsNrs = (new SportRange(0, $gameRound->nrOfPlaces - 1))->toArray();
            while (count($seatsNrs) > 1 ) {
                $homeAwaySeats[] = new OneVsOneHomeAway( array_shift($seatsNrs), array_pop($seatsNrs));
            }
        }

        $placeNrs = (new SportRange(1, $gameRound->nrOfPlaces))->toArray();

        for( $gameRoundNr = 1; $gameRoundNr < $gameRound->nrOfPlaces; $gameRoundNr++) {

            foreach( $homeAwaySeats as $homeAwaySeat ) {
                $homeAway = new OneVsOneHomeAway( $placeNrs[$homeAwaySeat->getHome()], $placeNrs[$homeAwaySeat->getAway()]);
                if( $swapHomeAways ) {
                    $homeAway = $homeAway->swap();
                }
                $gameRound->add($homeAway);
            }

            $firstPlaceNr = array_shift($placeNrs);
            array_unshift($placeNrs, array_pop($placeNrs));
            array_unshift($placeNrs, $firstPlaceNr);

            $gameRound = $gameRound->createNext();
        }
        return $gameRound;

//        $nrOfPlacesRange = new SportRange(1, $gameRound->nrOfPlaces);
//        $duoPlaceNrIt = new DuoPlaceNrIterator($nrOfPlacesRange);
//        while( $duoPlaceNr = $duoPlaceNrIt->current() ) {
//
//            if ($this->shouldSwap($duoPlaceNr)) {
//                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrTwo, $duoPlaceNr->placeNrOne);
//            } else {
//                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrOne, $duoPlaceNr->placeNrTwo);
//            }
//            $homeAways[] = $swap ? $homeAway->swap() : $homeAway;
//            $duoPlaceNrIt->next();
//        }



    }

    private function assignHomeAways2NPlus1(AgainstGameRound $gameRound, bool $swapHomeAways): AgainstGameRound {
        throw new \Exception('implement N % 2 1');
//        $homeAways = [];
//
//
//
//        // 5 seats          : 1 vs 4 & 2 vs 3, 0 sits out
//        // 5 placeNrs       : 1, 2, 3, 4, 5
//        // placeNr 1 stays at seat 1, other placeNrs go to the next seatNumber
//        // roundNumber
//        // 1                  ? vs ?  3 vs ?
//        // 2                  ? vs ?  4 vs ?
//        // 3                  ? vs ?  2 vs ?
//        // 4
//        // 1 vs 1 : (placeNrOne + placeNrTwo) % 2) is 0 means swap
//        else {
//            if( $gameRound->nrOfPlaces === 5 ) {
//                $seatConfig = [2, 3] [4, 1] // placeNr 1 takes seat 2, placeNr 5 "sits out"
//;
//
//            }
//        }
//
//
//        $nrOfVenues = floor( $gameRound->nrOfPlaces / 2);
//        while( $nrOfVenues-- > 0) {
//            $venues = [new Venue(1,4), new Venue(2,3)]
//            }
//        // bij 5 spelers
//        //      1 2
//        // 0
//        //      4, 3
//
//        // bij 5 spelers
//        // 0 5, 1 4, 2 3
//
//        $placeNrs = (new SportRange(1, $gameRound->nrOfPlaces))->toArray();
//        for( $rotateNr = 1; $rotateNr <= $h2hNr; $rotateNr++) {
//            $placeNrs[] = array_shift($placeNrs);
//        }
//
//        $seatZero = array_shift($placeNrs);
//
//        // next placeNrs should be
//
//        // 2 and 3 vs. 4 and 6     5 and 1 vs. 7 and 0
//
//
//        $swap = ($h2hNr % 2) === 0;
//
//
//        $nrOfPlacesRange = new SportRange(1, $gameRound->nrOfPlaces);
//        $duoPlaceNrIt = new DuoPlaceNrIterator($nrOfPlacesRange);
//        while( $duoPlaceNr = $duoPlaceNrIt->current() ) {
//
//            if ($this->shouldSwap($duoPlaceNr)) {
//                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrTwo, $duoPlaceNr->placeNrOne);
//            } else {
//                $homeAway = new OneVsOneHomeAway($duoPlaceNr->placeNrOne, $duoPlaceNr->placeNrTwo);
//            }
//            $homeAways[] = $swap ? $homeAway->swap() : $homeAway;
//            $duoPlaceNrIt->next();
//        }
//        return $homeAways;
//
//
//        return $gameRound->createNext();
    }

    protected function shouldSwap(DuoPlaceNr $duoPlaceNr): bool
    {
        return (($duoPlaceNr->placeNrOne + $duoPlaceNr->placeNrTwo) % 2) === 0;
    }



//    /**
//     * @param AgainstH2hWithNrOfPlaces $againstH2hWithNrOfPlaces
//     * @param H2HHomeAwayGenerator $homeAwayCreator
//     * @param list<OneVsOneHomeAway> $homeAwaysForGameRound
//     * @param list<OneVsOneHomeAway> $homeAways
//     * @param AgainstGameRound $gameRound
//     * @param int $currentNrOfH2h
//     * @param int $nrOfHomeAwaysTried
// * @return bool
//     */
//    protected function assignGameRound(
//        AgainstH2hWithNrOfPlaces        $againstH2hWithNrOfPlaces,
//        H2HHomeAwayGenerator            $homeAwayCreator,
//        array                           $homeAwaysForGameRound,
//        array                           $homeAways,
//        AgainstGameRound                $gameRound,
//        int                             $currentNrOfH2h,
//        int                             $nrOfHomeAwaysTried = 0
//    ): bool {
//        if ($currentNrOfH2h > $againstH2hWithNrOfPlaces->getSportVariant()->nrOfH2h ) {
//            return true;
//        }
//
//        if ($this->isGameRoundCompleted($againstH2hWithNrOfPlaces, $gameRound)) {
////            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");
//
//            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
//            if (count($homeAways) === 0) {
//                $homeAways = $homeAwayCreator->createForOneH2h($againstH2hWithNrOfPlaces->getNrOfPlaces(), $currentNrOfH2h);
//            }
//
////            if ($gameRound->getNumber() === 14) {
////                $this->gameRoundOutput->output($gameRound);
////                $this->outputUnassignedTotals($homeAways);
////                $this->outputUnassignedHomeAways($homeAways);
////                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
////                $qw = 12;
////            }
//
//
//            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
//            //                $this->gameRoundOutput->output($gameRound);
//            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
//            $nextHomeAways = $homeAways;
////
////            if ($gameRound->getNumber() === 14) {
////                $this->gameRoundOutput->outputHomeAways($sortedHomeAways, $gameRound, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
////            }
//
////            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
//            // $gamesList = array_values($gamesForBatchTmp);
////            shuffle($homeAways);
//            return $this->assignGameRound(
//                $againstH2hWithNrOfPlaces,
//                $homeAwayCreator,
//                $nextHomeAways,
//                $homeAways,
//                $nextGameRound,
//                ++$currentNrOfH2h
//            );
//        }
//
//        if ($nrOfHomeAwaysTried === count($homeAwaysForGameRound)) {
//            return false;
//        }
//        $homeAway = array_shift($homeAwaysForGameRound);
//        if ($homeAway === null) {
//            return false;
//        }
//
//        if ( $gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway) ) {
//
//            $gameRound->add($homeAway);
//            $statisticsCalculatorTry = $statisticsCalculator->addHomeAway($homeAway);
//
////            if ($gameRound->getNumber() === 15 ) {
////                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround 15');
////                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround 15');
////                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
////                $qw = 12;
////            }
//            $homeAwaysForGameRoundTmp = array_values(
//                array_filter(
//                    $homeAwaysForGameRound,
//                    function (OneVsOneHomeAway $homeAway) use ($gameRound): bool {
//                        return !$gameRound->isSomeHomeAwayPlaceNrParticipating($homeAway);
//                    }
//                )
//            );
//            if ($this->assignGameRound(
//                $againstH2hWithNrOfPlaces,
//                $homeAwayCreator,
//                $homeAwaysForGameRoundTmp,
//                $homeAways,
//                $statisticsCalculatorTry,
//                $gameRound
//            )) {
//                return true;
//            }
//            $this->releaseHomeAway($gameRound, $homeAway);
//        }
//        $homeAwaysForGameRound[] = $homeAway;
//        ++$nrOfHomeAwaysTried;
//        return $this->assignGameRound(
//            $againstH2hWithNrOfPlaces,
//            $homeAwayCreator,
//            $homeAwaysForGameRound,
//            $homeAways,
//            $statisticsCalculator,
//            $gameRound,
//            $nrOfHomeAwaysTried
//        );
//    }
}
