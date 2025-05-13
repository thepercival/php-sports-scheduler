<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules\CycleCreators;

use phpDocumentor\Reflection\Types\Boolean;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

class CycleCreatorAgainstOneVsOne extends CycleCreatorAgainstAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createRootCycleAndGames(
        ScheduleAgainstOneVsOne $scheduleAgainstOneVsOne
    ): ScheduleCycleAgainstOneVsOne
    {
        $cycle = new ScheduleCycleAgainstOneVsOne($scheduleAgainstOneVsOne);

        for ($cycleNr = 1; $cycleNr <= $scheduleAgainstOneVsOne->nrOfCycles; $cycleNr++) {
            $this->createRootCyclePartAndGames($cycle, $scheduleAgainstOneVsOne->scheduleWithNrOfPlaces->nrOfPlaces);
            if( $cycleNr < $scheduleAgainstOneVsOne->nrOfCycles ) {
                $cycle = $cycle->createNext();
            }
        }
        return $cycle->getFirst();
    }

    private function createRootCyclePartAndGames(ScheduleCycleAgainstOneVsOne $cycle, int $nrOfPlaces): void {

        $rootCyclePart = $cycle->firstPart;
        $swapHomeAways = ($cycle->getNumber() % 2) === 0;
        if( $nrOfPlaces % 2 === 0 ) {
            $this->create2NGames($rootCyclePart, $swapHomeAways);
        } else {
            $this->create2NPlus1Games($rootCyclePart, $swapHomeAways);
        }
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
    private function create2NGames(ScheduleCyclePartAgainstOneVsOne $rootCyclePart, bool $swap): void {
        $nrOfPlaces = $rootCyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        $homeAways = [];
        {
            $seatsNrs = (new SportRange(0, $nrOfPlaces - 1))->toArray();
            while (count($seatsNrs) > 1 ) {
                $homeAways[] = new OneVsOneHomeAway( array_shift($seatsNrs), array_pop($seatsNrs));
            }
        }

        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        $cyclePart = $rootCyclePart;
        for( $cyclePartNr = 1; $cyclePartNr < $nrOfPlaces; $cyclePartNr++) {

            foreach( $homeAways as $homeAway ) {
                $homePlaceNr = $placeNrs[$homeAway->getHome()];
                $awayPlaceNr = $placeNrs[$homeAway->getAway()];
                if( $this->shouldSwap($homePlaceNr,$awayPlaceNr)) {
                    $homeAway = new OneVsOneHomeAway( $awayPlaceNr, $homePlaceNr);
                } else {
                    $homeAway = new OneVsOneHomeAway( $homePlaceNr, $awayPlaceNr);
                }
                if($swap) {
                    $homeAway = $homeAway->swap();
                }
                $cyclePart->addGame(new ScheduleGameAgainstOneVsOne($cyclePart, $homeAway));
            }

            $firstPlaceNr = array_shift($placeNrs);
            array_unshift($placeNrs, array_pop($placeNrs));
            array_unshift($placeNrs, $firstPlaceNr);

            if( $cyclePartNr < ($nrOfPlaces - 1 ) ) {
                $cyclePart = $cyclePart->createNext();
            }

        }
    }

    // ///////////////////////////////////////////////////////////
    //
    // 3 seats seat-solution : 0 & 1 vs 2
    // 3 placeNrs       : 1, 2, 3
    // seat 0 sits out, all players go to next seat, except last seat goes to seat 0

    //                                                  placeNrs
    // gameNr | seat-solution   1 vs 2 & 0 |            1 2 3
    // -------|----------------------------|-----------------
    // 1      | placeNrs        1 vs 2 & 3 | seats      1 2 0
    // 2      | placeNrs        1 vs 3 & 2 | seats      2 0 1
    // 3      | placeNrs        2 vs 3 & 1 | seats      0 1 2
    //
    // 5 seats          :
    // 5 seats seat-solution : 1 vs 4 & 2 vs 3  0 sits out
    // 5 placeNrs            : 1, 2, 3, 4, 5
    // seat 0 sits out, all players go to next seat, except last seat goes to seat 0

    //                                                           placeNrs
    // gameNr | seat-solution   1 vs 4 & 2 vs 3 & 0 |            1 2 3 4 5
    // -------|-------------------------------------|---------------------
    // 1      | placeNrs        1 vs 4 & 2 vs 3 & 5 | seats      1 2 3 4 0
    // 2      | placeNrs        5 vs 3 & 1 vs 2 & 4 | seats      2 3 4 0 1
    // 3      | placeNrs        4 vs 2 & 5 vs 1 & 3 | seats      3 4 0 1 2
    // 4      | placeNrs        3 vs 1 & 4 vs 5 & 2 | seats      4 0 1 2 3
    // 5      | placeNrs        2 vs 5 & 3 vs 4 & 1 | seats      0 1 2 3 4
    //
    // 1 vs 1 : (placeNrOne + placeNrTwo) % 2) is 0 means swap

    private function create2NPlus1Games(ScheduleCyclePartAgainstOneVsOne $cyclePart, bool $swap): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        $seatsNrs = (new SportRange(0, $nrOfPlaces - 1))->toArray();
//        $sitoutSeat = array_shift($seatsNrs);
        $homeAwaySeats = [];
        {
            while (count($seatsNrs) > 1 ) {
                $homeAwaySeats[] = new OneVsOneHomeAway( array_shift($seatsNrs), array_pop($seatsNrs));
            }
        }

        $cyclePartIt = $cyclePart;
        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();
        array_unshift($placeNrs, array_pop($placeNrs));

        for( $cyclePartNr = 1; $cyclePartNr <= $nrOfPlaces; $cyclePartNr++) {

            foreach( $homeAwaySeats as $homeAwaySeat ) {
                $homePlaceNr = $placeNrs[$homeAwaySeat->getHome()];
                $awayPlaceNr = $placeNrs[$homeAwaySeat->getAway()];
                if( $this->shouldSwap($homePlaceNr,$awayPlaceNr)) {
                    $homeAway = new OneVsOneHomeAway( $awayPlaceNr, $homePlaceNr);
                } else {
                    $homeAway = new OneVsOneHomeAway( $homePlaceNr, $awayPlaceNr);
                }
                if($swap) {
                    $homeAway = $homeAway->swap();
                }
                $cyclePartIt->addGame(new ScheduleGameAgainstOneVsOne($cyclePart, $homeAway));
            }

            array_unshift($placeNrs, array_pop($placeNrs));

            $cyclePartIt = $cyclePartIt->createNext();
        }
    }

    protected function shouldSwap(int $homePlaceNr, int $awayPlaceNr): bool
    {
        $sumIsEven = (($homePlaceNr + $awayPlaceNr) % 2) === 0;
        return ( $sumIsEven && $homePlaceNr < $awayPlaceNr )
            || ( !$sumIsEven && $homePlaceNr > $awayPlaceNr );
    }
}
