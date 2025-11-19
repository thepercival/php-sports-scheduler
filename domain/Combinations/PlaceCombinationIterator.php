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
final class PlaceCombinationIterator implements Iterator
{
    /**
     * @var list<PlaceIterator>
     */
    protected array $placeIterators;
    protected int $nrOfIncrements = 1;

    /**
     * @param Poule $poule
     * @param list<Place> $startPlaces
     * @param int $maxNrOfIncrements
     */
    public function __construct(Poule $poule, array $startPlaces, protected int $maxNrOfIncrements)
    {
        $this->placeIterators = array_map(fn (Place $place) => new PlaceIterator($poule, $place->getPlaceNr()), $startPlaces);
    }

    #[\Override]
    public function current(): PlaceCombination
    {
        $places = array_map(fn (PlaceIterator $placeIterator) => $placeIterator->current(), $this->placeIterators);
        return new PlaceCombination($places);
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
