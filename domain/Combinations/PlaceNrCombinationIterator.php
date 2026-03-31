<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<string|int, PlaceNrCombination>
 */
final class PlaceNrCombinationIterator implements Iterator
{
    /**
     * @var list<PlaceNrIterator>
     */
    protected array $placeIterators;
    protected int $nrOfIncrements = 1;

    /**
     * @param int $nrOfPlaces
     * @param list<Place> $startPlaces
     * @param int $maxNrOfIncrements
     */
    public function __construct(int $nrOfPlaces, array $startPlaces, protected int $maxNrOfIncrements)
    {
        $this->placeIterators = array_map(fn (Place $place) => new PlaceNrIterator($nrOfPlaces, $place->getPlaceNr()), $startPlaces);
    }

    #[\Override]
    public function current(): PlaceNrCombination
    {
        $placeNrs = array_map(fn (PlaceNrIterator $placeIterator) => $placeIterator->current(), $this->placeIterators);
        return new PlaceNrCombination($placeNrs);
    }

    #[\Override]
    public function next(): void
    {
        $this->nrOfIncrements++;
        foreach ($this->placeIterators as $placeIterator) {
            //   for( $i = 0 ; $i < $this->delta ;$i++) {
            $placeIterator->next();
            //     }
        }
    }

    #[\Override]
    public function key(): string
    {
        return '' . ((string)$this->current());
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->nrOfIncrements <= $this->maxNrOfIncrements;
    }

    #[\Override]
    public function rewind(): void
    {
    }
}
