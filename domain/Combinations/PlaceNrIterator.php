<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;

/**
 * @implements Iterator<int, int>
 */
final class PlaceNrIterator implements Iterator
{
    private int $current;

    public function __construct(protected int $nrOfPlaces, int $startNr)
    {
        $this->current = $startNr;
    }

    #[\Override]
    public function current(): int
    {
        return $this->current;
    }

    #[\Override]
    public function next(): void
    {
        if ($this->current === $this->nrOfPlaces) {
            $this->current = 1;
        } else {
            $this->current++;
        }
    }

    #[\Override]
    public function key(): int
    {
        return $this->current;
    }

    #[\Override]
    public function valid(): bool
    {
        return true;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->current = 1;
    }
}
