<?php

declare(strict_types=1);

namespace SportsScheduler\Iterators\SportsIterators;

use SportsHelpers\SportRange;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

/**
 * @implements \Iterator<string, SportWithNrOfFieldsAndNrOfCycles|null>
 */
final class TogetherSportIterator implements \Iterator
{

    protected int $nrOfFields;
    protected int $nrOfGamePlaces;
    protected int $nrOfCycles;
    protected SportWithNrOfFieldsAndNrOfCycles|null $current;

    public function __construct(
        protected SportRange $gamePlacesRange,
        protected SportRange $fieldRange,
        protected SportRange $nrOfCyclesRange
    ) {
        $this->rewind();
    }

    protected function rewindNrOfGamePlaces(): void
    {
        $this->nrOfGamePlaces = $this->gamePlacesRange->getMin();
        if ($this->nrOfGamePlaces < 1) {
            $this->nrOfGamePlaces = 1;
        }

        $this->rewindNrOfFields();
    }

    protected function rewindNrOfFields(): void
    {
        $this->nrOfFields = $this->fieldRange->getMin();
        $this->rewindNrOfCycles();
    }

    protected function rewindNrOfCycles(): void
    {
        $this->nrOfCycles = $this->nrOfCyclesRange->getMin();
    }

    #[\Override]
    public function current(): SportWithNrOfFieldsAndNrOfCycles|null
    {
        return $this->current;
    }

    #[\Override]
    public function key(): string
    {
        throw new \Exception('has no key');
    }

    #[\Override]
    public function next(): void
    {
        if ($this->current === null) {
            return;
        }
        if ($this->incrementValue() === false) {
            $this->current = null;
            return;
        }
        $this->current = $this->createSport();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->rewindNrOfGamePlaces();
        $this->current = $this->createSport();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->current !== null;
    }

    protected function createSport(): SportWithNrOfFieldsAndNrOfCycles
    {
        return new SportWithNrOfFieldsAndNrOfCycles(
            new TogetherSport($this->nrOfGamePlaces),
            $this->nrOfFields,
            $this->nrOfCycles
        );
    }

    protected function incrementValue(): bool
    {
        return $this->incrementNrOfCycles();
    }

    protected function incrementNrOfCycles(): bool
    {
        if ($this->nrOfCycles === $this->nrOfCyclesRange->getMax()) {
            return $this->incrementNrOfFields();
        }
        $this->nrOfCycles++;
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        if ($this->nrOfFields === $this->fieldRange->getMax()) {
            return $this->incrementNrOfGamePlaces();
        }
        $this->nrOfFields++;
        $this->rewindNrOfCycles();
        return true;
    }

    protected function incrementNrOfGamePlaces(): bool
    {
        if ($this->nrOfGamePlaces === $this->gamePlacesRange->getMax()) {
            return false;
        }
        $this->nrOfGamePlaces++;
        $this->rewindNrOfFields();
        return true;
    }
}
