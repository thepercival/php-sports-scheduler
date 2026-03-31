<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\StatisticsCalculators;


use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\Amounts\AmountCalculator;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombinationCounterMap;
use SportsPlanning\Combinations\PlaceNrCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;

abstract class StatisticsCalculatorAbstract
{
    public function __construct(
        protected RangedPlaceNrCombinationCounterMap $assignedHomeMap,
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
     * @param PlaceNrCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAgainstCombinationAssigned(PlaceNrCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfLeastAmount = 0;
        foreach ($homeAway->getAgainstPlaceNrCombinations() as $againstPlaceNrCombination ) {
            $amountAssigned = $map->count($againstPlaceNrCombination);
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
     * @param PlaceNrCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastAssigned(PlaceNrCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaceNrs() as $placeNr) {
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

    /**
     * @param PlaceNrCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return LeastAmountAssigned
     */
    protected function getLeastWithCombinationAssigned(PlaceNrCombinationCounterMap $map, HomeAway $homeAway): LeastAmountAssigned
    {
        $leastAmount = -1;
        $nrOfSides = 0;
        foreach ([AgainstSide::Home,AgainstSide::Away] as $side ) {
            $sidePlaceNrCombination = $homeAway->get($side);
            $amountAssigned = $map->count($sidePlaceNrCombination);
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
        $header .= ' allowedRange : ' . ((string)$allowedRange);
        $nrOfPossiblities = count( $this->assignedHomeMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedHomeMap->getNrOfPlaceNrCombinationsBelowMinimum();
        $header .= '/' . (new AmountCalculator($nrOfPossiblities, $allowedRange))->maxCountBeneathMinimum();
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $map = $this->assignedHomeMap->getMap()->getAmountMap();
        $mapOutput = $prefix . 'map: ';
        foreach($map as $amount) {
            $mapOutput .= ((string)$amount)  . ', ';
        }
        $this->logger->info($prefix . $mapOutput . 'difference : '.$this->assignedHomeMap->getAmountDifference());

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedHomeMap->getMap()->getList() as $counterIt ) {
            $line .= ((string)$counterIt->getPlaceNrCombination()) . ' ' . $counterIt->count() . 'x, ';
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
