<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;


use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\Amount\Calculator;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsScheduler\Combinations\StatisticsCalculator\LeastAmountAssigned;

abstract class StatisticsCalculator
{
    public function __construct(
        protected RangedPlaceCombinationCounterMap $assignedHomeMap,
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
     * @param PlaceCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAgainstCombinationAssigned(PlaceCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
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
     * @param PlaceCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAssigned(PlaceCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
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
     * @param PlaceCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastWithCombinationAssigned(PlaceCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
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
        $allowedRange = $this->assignedHomeMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $nrOfPossiblities = count( $this->assignedHomeMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedHomeMap->getNrOfPlaceCombinationsBelowMinimum();
        $header .= '/' . (new Calculator($nrOfPossiblities, $allowedRange))->maxCountBeneathMinimum();
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $map = $this->assignedHomeMap->getMap()->getAmountMap();
        $mapOutput = $prefix . 'map: ';
        foreach($map as $amount) {
            $mapOutput .= $amount  . ', ';
        }
        $this->logger->info($prefix . $mapOutput . 'difference : '.$this->assignedHomeMap->getAmountDifference());

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedHomeMap->getMap()->getList() as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
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
