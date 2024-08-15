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
    protected PlaceNrIterator $homePlaceNrIterator;
    protected PlaceNrIterator $awayPlaceNrIterator;

    public function __construct(private readonly SportRange $range)
    {
        if( $this->range->difference() < 1) {
            throw new \Exception('range should be at least one');
        }
        $this->homePlaceNrIterator = new PlaceNrIterator($range);
        $this->awayPlaceNrIterator = new PlaceNrIterator($range);
    }

    public function current(): DuoPlaceNr|null
    {
        $homePlaceNr = $this->homePlaceNrIterator->current();
        $awayPlaceNr = $this->awayPlaceNrIterator->current();
        if( $homePlaceNr === null || $awayPlaceNr === null) {
            return null;
        }
        return new DuoPlaceNr($homePlaceNr, $awayPlaceNr);
    }

    public function next(): void
    {
        $homePlaceNr = $this->homePlaceNrIterator->current();
        $awayPlaceNr = $this->awayPlaceNrIterator->current();
        if( $homePlaceNr === null || $awayPlaceNr === null) {
            return;
        }
        if( $awayPlaceNr < $this->range->getMax() ) {
            $this->awayPlaceNrIterator->next();
        }
        else if( $awayPlaceNr === $this->range->getMax() ) {
            $this->homePlaceNrIterator->next();
            // set away to home + 1
            $homePlaceNr = $this->homePlaceNrIterator->current();
            if( $homePlaceNr !== null) {
                $awayPlaceNr = $this->awayPlaceNrIterator->current();
                while ( $awayPlaceNr !== null && $awayPlaceNr <= $homePlaceNr ) {
                    $this->awayPlaceNrIterator->next();
                    $awayPlaceNr = $this->awayPlaceNrIterator->current();
                }
            }
        }
    }

    public function key(): string
    {
        return '' . $this->current();
    }

    public function valid(): bool
    {
        return $this->current() !== null;
    }

    public function rewind(): void
    {
        $this->homePlaceNrIterator->rewind();
        $this->awayPlaceNrIterator->rewind();
    }
}
