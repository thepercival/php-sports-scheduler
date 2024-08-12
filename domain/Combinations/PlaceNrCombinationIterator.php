<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use Iterator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<string|int, PlaceCombination>
 */
class PlaceNrCombinationIterator implements Iterator
{
    /**
     * @var list<PlaceNrIterator>
     */
    protected array $placeNrIterators;
    protected int $nrOfIncrements = 1;

    /**
     * @param Poule $poule
     * @param list<Place> $startPlaces
     * @param int $maxNrOfIncrements
     */
    public function __construct(Poule $poule, array $startPlaces, protected int $maxNrOfIncrements)
    {
        $this->placeNrIterators = array_map(fn (Place $place) => new PlaceNrIterator($poule, $place->getPlaceNr()), $startPlaces);
    }

    public function current(): PlaceCombination
    {
        $placeNrs = array_map(fn (PlaceNrIterator $placeIterator) => $placeIterator->current(), $this->placeNrIterators);
        return new PlaceCombination($places);
    }

    public function next(): void
    {
        $this->nrOfIncrements++;
        foreach ($this->placeNrIterators as $placeNrIterator) {
            //   for( $i = 0 ; $i < $this->delta ;$i++) {
            $placeNrIterator->next();
            //     }
        }
    }

    public function key(): string
    {
        return '' . $this->current();
    }

    public function valid(): bool
    {
        return $this->nrOfIncrements <= $this->maxNrOfIncrements;
    }

    public function rewind(): void
    {
    }
}
