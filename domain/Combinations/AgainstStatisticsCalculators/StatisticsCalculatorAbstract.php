<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountCalculator;
use SportsPlanning\Counters\Maps\RangedPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

abstract class StatisticsCalculatorAbstract
{
//    public function __construct(
//        protected RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
//        protected int $nrOfHomeAwaysAssigned,
//        protected LoggerInterface $logger
//    )
//    {
//    }
//
//    public function getNrOfHomeAwaysAssigned(): int {
//        return $this->nrOfHomeAwaysAssigned;
//    }
//
//    abstract public function allAssigned(): bool;
//
//    /**
//     * @param RangedPlaceNrCounterMap $map
//     * @param OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway
//     * @return LeastAmountAssigned
//     */
//    protected function getLeastAssigned(
//        RangedPlaceNrCounterMap $map,
//        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): LeastAmountAssigned
//    {
//        $leastAmount = -1;
//        $nrOfPlaces = 0;
//        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
//            $amountAssigned = $map->count($placeNr);
//            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
//                $leastAmount = $amountAssigned;
//                $nrOfPlaces = 0;
//            }
//            if ($amountAssigned === $leastAmount) {
//                $nrOfPlaces++;
//            }
//        }
//        return new LeastAmountAssigned($leastAmount, $nrOfPlaces);
//    }
//
//    public function outputHomeTotals(string $prefix, bool $withDetails): void
//    {
//        $header = 'HomeTotals : ';
//        $allowedRange = $this->rangedHomeNrCounterMap->allowedRange;
//        $header .= ' allowedRange : ' . $allowedRange;
//
////        $rangedHomeCounterReport = $this->rangedHomeNrCounterMap->calculateReport();
//        $nrOfPossiblities = $this->rangedHomeNrCounterMap->getNOfPossibleCombinations();
//        $header .= ', belowMinimum(total) : ' . $rangedHomeCounterReport->getTotalBelowMinimum();
//
//        $amountCalculator = new AmountCalculator($allowedRange);
//        $amountSmaller = $amountCalculator->calculateCumulativeSmallerThanMinAmount( [ 0 => new Amount(0, $nrOfPossiblities ) ] );
//        $header .= '/' . $amountSmaller;
//        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
//        $this->logger->info($prefix . $header);
//
//        $this->logger->info($prefix . 'MOVED TO MAP!!!');
//
//        $map = $rangedHomeCounterReport->getAmountMap();
//        $mapOutput = $prefix . 'map: ';
//        foreach($map as $amount) {
//            $mapOutput .= $amount  . ', ';
//        }
//        $this->logger->info($prefix . $mapOutput . 'difference : '.$rangedHomeCounterReport->getAmountDifference());
//
//        if( !$withDetails ) {
//            return;
//        }
//
//        $this->logger->info($prefix . 'ADD DETAILS HERE!!!');
//
//        // ADD HERE DETAILS
//        $prefix =  '    ' . $prefix;
//        // $this->rangedHomeNrCounterMap->output($this->logger, $prefix, $header);
//    }
}
