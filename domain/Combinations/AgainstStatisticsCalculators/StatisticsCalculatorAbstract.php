<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\Amount\Calculator;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedDuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

abstract class StatisticsCalculatorAbstract
{
    public function __construct(
        protected RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
        protected int $nrOfHomeAwaysAssigned,
        protected LoggerInterface $logger
    )
    {
    }

    public function getNrOfHomeAwaysAssigned(): int {
        return $this->nrOfHomeAwaysAssigned;
    }

    abstract public function allAssigned(): bool;

    /**
     * @param RangedPlaceNrCounterMap|PlaceNrCounterMap $map
     * @param OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAssigned(
        RangedPlaceNrCounterMap|PlaceNrCounterMap $map,
        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            $amountAssigned = $map->count($placeNr);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfPlaces = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfPlaces++;
            }
        }
        return new LeastAmountAssigned($leastAmount, $nrOfPlaces);
    }

    protected function getLeastAgainstCombinationAssigned(
        RangedDuoPlaceNrCounterMap $map,
        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfLeastAmount = 0;
        foreach ($homeAway->createAgainstDuoPlaceNrs() as $againstDuoPlaceNr ) {
            $amountAssigned = $map->count($againstDuoPlaceNr);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfLeastAmount = 1;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfLeastAmount++;
            }
        }
        return new LeastAmountAssigned($leastAmount, $nrOfLeastAmount);
    }

    protected function getLeastWithCombinationAssigned(
        RangedDuoPlaceNrCounterMap $map,
        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfSides = 0;
        foreach ([Side::Home,Side::Away] as $side ) {
            $sidePlaceCombination = $homeAway->get($side);
            $amountAssigned = $map->count($sidePlaceCombination);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfSides = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfSides++;
            }
        }
        return new LeastAmountAssigned($leastAmount, $nrOfSides);
    }

    public function outputHomeTotals(string $prefix, bool $withDetails): void
    {
        $header = 'HomeTotals : ';
        $allowedRange = $this->rangedHomeNrCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedHomeCounterReport = $this->rangedHomeNrCounterMap->calculateReport();
        $nrOfPossiblities = $rangedHomeCounterReport->getNOfPossibleCombinations();
        $header .= ', belowMinimum(total) : ' . $rangedHomeCounterReport->getTotalBelowMinimum();
        $header .= '/' . (new Calculator($nrOfPossiblities, $allowedRange))->maxCountBelowMinimum();
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $map = $rangedHomeCounterReport->getAmountMap();
        $mapOutput = $prefix . 'map: ';
        foreach($map as $amount) {
            $mapOutput .= $amount  . ', ';
        }
        $this->logger->info($prefix . $mapOutput . 'difference : '.$rangedHomeCounterReport->getAmountDifference());

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach($this->rangedHomeNrCounterMap->copyPlaceNrCounters() as $placeNrCounter ) {
            $line .= $placeNrCounter . ', ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info($prefix . $line);
        }
    }
}
