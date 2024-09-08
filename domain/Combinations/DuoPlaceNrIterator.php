<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<string, DuoPlaceNr|null>
 */
class DuoPlaceNrIterator implements Iterator
{
    protected PlaceNrIterator $currentHomePlaceNrIterator;
    protected PlaceNrIterator $currentAwayPlaceNrIterator;

    /**
     * @param SportRange $range
     * @param list<int>|null $exceptionPlaceNrs
     */
    public function __construct(
        private readonly SportRange $range,
        array|null $exceptionPlaceNrs = null
    )
    {
        if( $this->range->getMin() < 1) {
            throw new \Exception('range->minPlaceNr should should be at least 1');
        }
        if( $this->range->difference() < 1) {
            throw new \Exception('range should be at least one');
        }
        $this->currentHomePlaceNrIterator = new PlaceNrIterator($range, $exceptionPlaceNrs);
        $this->currentAwayPlaceNrIterator = new PlaceNrIterator($range, $exceptionPlaceNrs);
        $this->rewind();
    }

    public function current(): DuoPlaceNr|null
    {
        $homePlaceNr = $this->currentHomePlaceNrIterator->current();
        $awayPlaceNr = $this->currentAwayPlaceNrIterator->current();

        if( $homePlaceNr === null || $awayPlaceNr === null ) {
            return null;
        }
        if( $homePlaceNr === $this->range->getMax() && $awayPlaceNr === $this->range->getMax() ) {
            return null;
        }
        return new DuoPlaceNr($homePlaceNr, $awayPlaceNr);
    }

    public function next(): void
    {
        $homePlaceNr = $this->currentHomePlaceNrIterator->current();
        $awayPlaceNr = $this->currentAwayPlaceNrIterator->current();
        $current = $this->current();
        if( $current === null ) {
            return;
        }
        if( $awayPlaceNr < $this->range->getMax() ) {
            $this->currentAwayPlaceNrIterator->next();
            return;
        }
        $this->currentHomePlaceNrIterator->next();
        if( $homePlaceNr === ($this->range->getMax() - 1) ) {
            return;
        }

        // set away to home + 1
        $newHomePlaceNr = $this->currentHomePlaceNrIterator->current();
        if( $newHomePlaceNr === null ) {
            return;
        }
        $this->currentAwayPlaceNrIterator->rewind();
        $newAwayPlaceNr = $newHomePlaceNr + 1;
        while (  $this->currentAwayPlaceNrIterator->current() !== $newAwayPlaceNr ) {
            $this->currentAwayPlaceNrIterator->next();
        }
    }

    public function key(): string
    {
        $current = $this->current();
        return $current !== null ? (string)$current : '';
    }

    public function valid(): bool
    {
        return $this->current() !== null;
    }

    public function rewind(): void
    {
        $this->currentHomePlaceNrIterator->rewind();
        $this->currentAwayPlaceNrIterator->rewind();
        $this->currentAwayPlaceNrIterator->next();
    }
}
