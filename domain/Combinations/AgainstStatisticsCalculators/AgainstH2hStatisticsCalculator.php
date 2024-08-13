<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;

class AgainstH2hStatisticsCalculator extends StatisticsCalculatorAbstract
{
    public function __construct(
        protected AgainstH2hWithNrOfPlaces $againstH2hWithNrOfPlaces,
        RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
        int $nrOfHomeAwaysAssigned,
        LoggerInterface $logger
    )
    {
        parent::__construct($rangedHomeNrCounterMap, $nrOfHomeAwaysAssigned, $logger);
    }

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstH2hWithNrOfPlaces->getTotalNrOfGames()) {
            return false;
        }
        return true;
    }

    public function addHomeAway(OneVsOneHomeAway $homeAway): self
    {
//        $amountCounterMapForSport = clone $this->amountNrCounterMapForSport;
//        foreach ($homeAway->getPlaces() as $place) {
//            $amountCounterMapForSport->addPlace($place);
//        }
        $rangedHomeNrCounterMap = clone $this->rangedHomeNrCounterMap;
        $rangedHomeNrCounterMap->incrementPlaceNr($homeAway->getHome());

        return new self(
            $this->againstH2hWithNrOfPlaces,
            $rangedHomeNrCounterMap,
            $this->nrOfHomeAwaysAssigned + 1,
//            $amountCounterMapForSport,
            $this->logger
        );
    }

    /**
     * @param list<OneVsOneHomeAway> $homeAways
     * @return list<OneVsOneHomeAway>
     */
    public function sortHomeAways(array $homeAways): array {
//        $time_start = microtime(true);

        $leastAmountAssigned = [];
        // $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->amountNrCounterMapForSport, $homeAway);
            // $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssignedPlaces($this->assignedHomeMap, $homeAway->getHome()->getPlaces());
        }
        uasort($homeAways, function (
            OneVsOneHomeAway $homeAwayA,
            OneVsOneHomeAway $homeAwayB
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
