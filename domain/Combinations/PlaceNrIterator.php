<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;
use SportsHelpers\SportRange;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<int, int|null>
 */
final class PlaceNrIterator implements Iterator
{
    private int|null $current;

    /**
     * @param SportRange $range
     * @param list<int>|null $exceptionPlaceNrs
     */
    public function __construct(
        private readonly SportRange $range,
        private readonly array|null $exceptionPlaceNrs = null
    )
    {
        $this->current = $range->getMin();
    }

    #[\Override]
    public function key(): int|null
    {
        return $this->current;
    }

    #[\Override]
    public function current(): int|null
    {
        return $this->current;
    }

    #[\Override]
    public function next(): void
    {
        if ($this->current === $this->range->getMax()) {
            $this->current = null;
        } else if( $this->current !== null ) {
            $this->current++;
            if( $this->exceptionPlaceNrs !== null && in_array($this->current, $this->exceptionPlaceNrs, true)) {
                $this->next();
            }
        }
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->current !== null;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->current = $this->range->getMin();
    }
}
