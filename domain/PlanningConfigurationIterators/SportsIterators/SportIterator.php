<?php

declare(strict_types=1);

namespace SportsScheduler\PlanningConfigurationIterators\SportsIterators;

use SportsHelpers\SportRange;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

/**
 * @implements \Iterator<string, SportWithNrOfFieldsAndNrOfCycles|null>
 */
class SportIterator implements \Iterator
{
    protected AgainstSportIterator|null $againstSportIterator;
    protected TogetherSportIterator $togetherSportIterator;
    protected SportWithNrOfFieldsAndNrOfCycles|null $current = null;

    public function __construct(
        private SportRange $rangeNrOfFields,
        private SportRange $rangeNrOfAgainstCycles,
        private SportRange $rangeNrOfTogetherCycles,
        private SportRange $rangeNrOfTogetherGamePlaces,
    ) {
        $this->rewind();
    }

    protected function rewindAgainst(): void
    {
        $this->againstSportIterator = null;
        $this->rewindTogether();
    }

    protected function rewindTogether(): void
    {
        $this->togetherSportIterator = new TogetherSportIterator(
            $this->rangeNrOfTogetherGamePlaces,
            $this->rangeNrOfFields,
            $this->rangeNrOfTogetherCycles
        );
    }

    public function current(): SportWithNrOfFieldsAndNrOfCycles|null
    {
        return $this->current;
    }

    public function key(): string
    {
        throw new \Exception('Not implemented');
    }

    public function next(): void
    {
        if ($this->current === null) {
            return;
        }

        if ($this->incrementValue() === false) {
            $this->current = null;
        }
        if( $this->togetherSportIterator->valid() ) {
            $this->current = $this->togetherSportIterator->current();
        } else if( $this->againstSportIterator?->valid()) {
            $this->current = $this->againstSportIterator->current();
        }
    }

    public function rewind(): void
    {
        $this->rewindAgainst();
        $this->current = $this->togetherSportIterator->current();
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }

    protected function incrementValue(): bool
    {
        return $this->incrementTogether();
    }

    protected function incrementTogether(): bool
    {
        $this->togetherSportIterator->next();
        if (!$this->togetherSportIterator->valid()) {
            return $this->incrementAgainst();
        }
        return true;
    }

    protected function incrementAgainst(): bool
    {
        if( $this->againstSportIterator === null ) {
            $this->againstSportIterator = new AgainstSportIterator(
                $this->rangeNrOfFields,
                $this->rangeNrOfAgainstCycles
            );
            return $this->againstSportIterator->valid();
        }
        $this->againstSportIterator->next();
        return $this->againstSportIterator->valid();
    }
}
