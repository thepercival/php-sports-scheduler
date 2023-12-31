<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;

class MultipleCombinationsCounter
{
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    protected array $counters;

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function __construct(array $placeCombinations)
    {
        $this->counters = [];
        foreach ($placeCombinations as $placeCombinationIt) {
            $this->counters[$placeCombinationIt->getIndex()] = new PlaceCombinationCounter($placeCombinationIt);
        }
    }

    public function addCombination(PlaceCombination $placeCombination): void
    {
        if (isset($this->counters[$placeCombination->getIndex()])) {
            $this->counters[$placeCombination->getIndex()]->increment();
        }
    }

    public function balanced(): bool
    {
        $count = null;
        foreach ($this->counters as $counter) {
            if ($count === null) {
                $count = $counter->count();
            }
            if ($count !== $counter->count()) {
                return false;
            }
        }
        return true;
    }

    public function count(PlaceCombination $placeCombination): int
    {
        if (!isset($this->counters[$placeCombination->getIndex()])) {
            return 0;
        }
        return $this->counters[$placeCombination->getIndex()]->count();
    }

    public function totalCount(): int
    {
        $totalCount = 0;
        foreach ($this->counters as $counter) {
            $totalCount += $counter->count();
        }
        return $totalCount;
    }

    public function __toString(): string
    {
        $lines = '';
        foreach ($this->counters as $counter) {
            $lines .= $counter . PHP_EOL;
        }
        return $lines;
    }
}
