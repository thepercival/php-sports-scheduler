<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsScheduler\Combinations\StatisticsCalculator;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;

class H2h extends StatisticsCalculator
{
    public function __construct(
        protected Againsth2hWithPoule $againstH2hWithPoule,
        RangedPlaceCounterMap $rangedHomeCounterMap,
        int $nrOfHomeAwaysAssigned,
        protected PlaceCounterMap $amountCounterMapForSport,
        LoggerInterface $logger
    )
    {
        parent::__construct($rangedHomeCounterMap, $nrOfHomeAwaysAssigned, $logger);
    }

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstH2hWithPoule->getTotalNrOfGames()) {
            return false;
        }
        return true;
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        $amountCounterMapForSport = clone $this->amountCounterMapForSport;
        foreach ($homeAway->getPlaces() as $place) {
            $amountCounterMapForSport->addPlace($place);
        }
        $rangedHomeCounterMap = clone $this->rangedHomeCounterMap;
        foreach ($homeAway->getHome()->getPlaces() as $place) {
            $rangedHomeCounterMap->addPlace($place);
        }

        return new self(
            $this->againstH2hWithPoule,
            $rangedHomeCounterMap,
            $this->nrOfHomeAwaysAssigned + 1,
            $amountCounterMapForSport,
            $this->logger
        );
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param LoggerInterface $logger
     * @return list<HomeAway>
     */
    public function sortHomeAways(array $homeAways, LoggerInterface $logger): array {
//        $time_start = microtime(true);

        $leastAmountAssigned = [];
        // $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->amountCounterMapForSport, $homeAway);
            // $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssignedPlaces($this->assignedHomeMap, $homeAway->getHome()->getPlaces());
        }
        uasort($homeAways, function (
            HomeAway $homeAwayA,
            HomeAway $homeAwayB
        ) use($leastAmountAssigned/*, $leastHomeAmountAssigned*/): int {

            $leastAmountAssignedA = $leastAmountAssigned[$homeAwayA->getIndex()];
            $leastAmountAssignedB = $leastAmountAssigned[$homeAwayB->getIndex()];
            if ($leastAmountAssignedA->amount !== $leastAmountAssignedB->amount) {
                return $leastAmountAssignedA->amount - $leastAmountAssignedB->amount;
            }
            if ($leastAmountAssignedA->nrOfPlaces !== $leastAmountAssignedB->nrOfPlaces) {
                return $leastAmountAssignedB->nrOfPlaces - $leastAmountAssignedA->nrOfPlaces;
            }
//            if( $this->allowedGppAgainstDifference < ScheduleCreator::MAX_ALLOWED_GPP_DIFFERENCE) {
//                $sportAmountAgainstA = $this->getAgainstAmountAssigned($homeAwayA);
//                $sportAmountAgainstB = $this->getAgainstAmountAssigned($homeAwayB);
//                if ($sportAmountAgainstA !== $sportAmountAgainstB) {
//                    return $sportAmountAgainstA - $sportAmountAgainstB;
//                }
//            }
//
//            if( $this->allowedGppWithDifference < ScheduleCreator::MAX_ALLOWED_GPP_DIFFERENCE) {
//                if ($this->useWith) {
//                    $amountWithA = $this->getWithAmountAssigned($homeAwayA);
//                    $amountWithB = $this->getWithAmountAssigned($homeAwayB);
//                    if ($amountWithA !== $amountWithB) {
//                        return $amountWithA - $amountWithB;
//                    }
//                }
//            }

//            list($amountHomeA, $nrOfPlacesHomeA) = $leastHomeAmountAssigned[$homeAwayA->getIndex()];
//            list($amountHomeB, $nrOfPlacesHomeB) = $leastHomeAmountAssigned[$homeAwayB->getIndex()];
//            if ($amountHomeA !== $amountHomeB) {
//                return $amountHomeA - $amountHomeB;
//            }
//            return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
            return 0;
        });
        //        $logger->info("sorting homeaways .. " . (microtime(true) - $time_start));
//        $logger->info('after sorting ');
//        (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
        return array_values($homeAways);
    }



    public function output(string $prefix): void {
        $this->outputHomeTotals($prefix, true);
    }
}
