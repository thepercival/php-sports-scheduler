<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<int, Place>
 */
final class PlaceIterator implements Iterator
{
    private int $current;

    public function __construct(protected Poule $poule, int $startNr)
    {
        $this->current = $startNr;
    }

    #[\Override]
    public function current(): Place
    {
        return $this->poule->getPlace($this->current);
    }

    #[\Override]
    public function next(): void
    {
        if ($this->current === $this->poule->getPlaces()->count()) {
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
