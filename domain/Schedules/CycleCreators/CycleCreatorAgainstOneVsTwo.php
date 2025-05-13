<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules\CycleCreators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsTwo;

class CycleCreatorAgainstOneVsTwo extends CycleCreatorAgainstAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createRootCycleAndGames(
        ScheduleAgainstOneVsTwo $scheduleAgainstOneVsTwo
    ): ScheduleCycleAgainstOneVsTwo
    {
        $cycle = new ScheduleCycleAgainstOneVsTwo($scheduleAgainstOneVsTwo);

        for ($cycleNr = 1; $cycleNr <= $scheduleAgainstOneVsTwo->nrOfCycles; $cycleNr++) {
            $this->createGames($cycle, $scheduleAgainstOneVsTwo->scheduleWithNrOfPlaces->nrOfPlaces);

            if( $cycleNr < $scheduleAgainstOneVsTwo->nrOfCycles ) {
                $cycle = $cycle->createNext();
            }
        }
        return $cycle->getFirst();
    }

    private function createGames(ScheduleCycleAgainstOneVsTwo $cycle, int $nrOfPlaces): void {

        $rootCyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);
        $swapHomeAways = ($cycle->getNumber() % 2) === 0;
        if( $nrOfPlaces % 3 === 0 ) {
            $this->create3NGames($rootCyclePart, $swapHomeAways);
        } else if( $nrOfPlaces % 3 === 1 ) {
            $this->create3NPlus1Games($rootCyclePart, $swapHomeAways);
        } else {
            $this->create3NPlus2Games($rootCyclePart, $swapHomeAways);
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
    private function create3NGames(ScheduleCyclePartAgainstOneVsTwo $rootCyclePart, bool $swap): void {

        $nrOfPlaces = $rootCyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        if( $swap ) {
            $nrOfPlaces++;
        }
        throw new \Exception('implement solution ' . $nrOfPlaces . '-1');
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

    private function create3NPlus1Games(ScheduleCyclePartAgainstOneVsTwo $cyclePart, bool $swap): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        if( $swap ) {
            $nrOfPlaces++;
        }
        throw new \Exception('implement solution ' . $nrOfPlaces . '-1');
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

    private function create3NPlus2Games(ScheduleCyclePartAgainstOneVsTwo $cyclePart, bool $swap): void {

        $nrOfPlaces = $cyclePart->cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        if( $swap ) {
            $nrOfPlaces++;
        }
        throw new \Exception('implement solution ' . $nrOfPlaces . '-1');
    }

    protected function shouldSwap(int $homePlaceNr, int $awayPlaceNr): bool
    {
        $sumIsEven = (($homePlaceNr + $awayPlaceNr) % 2) === 0;
        return ( $sumIsEven && $homePlaceNr < $awayPlaceNr )
            || ( !$sumIsEven && $homePlaceNr > $awayPlaceNr );
    }
}
