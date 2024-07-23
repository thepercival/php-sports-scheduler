<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;


use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\Amount\Calculator;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsScheduler\Combinations\StatisticsCalculator\LeastAmountAssigned;

abstract class StatisticsCalculator
{
    public function __construct(
        protected RangedPlaceCounterMap $rangedHomeCounterMap,
        protected int $nrOfHomeAwaysAssigned,
        protected LoggerInterface $logger
    )
    {
    }

    public function getNrOfHomeAwaysAssigned(): int {
        return $this->nrOfHomeAwaysAssigned;
    }

    abstract public function addHomeAway(HomeAway $homeAway): self;

    abstract public function allAssigned(): bool;

    /**
     * @param RangedPlaceCounterMap|PlaceCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAssigned(RangedPlaceCounterMap|PlaceCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $map->count($place);
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

    /**
     * @param RangedPlaceCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAgainstCombinationAssigned(RangedPlaceCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfLeastAmount = 0;
        foreach ($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            $amountAssigned = $map->count($againstPlaceCombination);
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

    /**
     * @param RangedPlaceCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastWithCombinationAssigned(RangedPlaceCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
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
        $allowedRange = $this->rangedHomeCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedHomeCounterReport = $this->rangedHomeCounterMap->calculateReport();
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
        foreach($this->rangedHomeCounterMap->copyPlaceCounterMap() as $counterIt ) {
            $line .= $counterIt->getPlace() . ' ' . $counterIt->count() . 'x, ';
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
