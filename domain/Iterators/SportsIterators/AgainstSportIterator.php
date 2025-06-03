<?php

declare(strict_types=1);

namespace SportsScheduler\Iterators\SportsIterators;

use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

/**
 * @implements \Iterator<string, SportWithNrOfFieldsAndNrOfCycles|null>
 */
class AgainstSportIterator implements \Iterator
{
    protected SportRange $sidePlacesRange;

    protected int $nrOfFields;
    protected int $nrOfHomePlaces;
    protected int $nrOfAwayPlaces;
    protected int $nrOfCycles;
    protected SportWithNrOfFieldsAndNrOfCycles|null $current;

    public function __construct(
        protected SportRange $fieldRange,
        protected SportRange $nrOfCyclesRange
    ) {
        $this->sidePlacesRange = new SportRange(1, 2);
        $this->rewind();
    }

    protected function rewindNrOfHomePlaces(): void
    {
        $this->nrOfHomePlaces = $this->sidePlacesRange->getMin();
        if ($this->nrOfHomePlaces < 1) {
            $this->nrOfHomePlaces = 1;
        }
        $this->rewindNrOfAwayPlaces();
    }

    protected function rewindNrOfAwayPlaces(): void
    {
        $this->nrOfAwayPlaces = $this->sidePlacesRange->getMin();
        if ($this->nrOfAwayPlaces < 1) {
            $this->nrOfAwayPlaces = 1;
        }
        if ($this->nrOfAwayPlaces < $this->nrOfHomePlaces) {
            $this->nrOfAwayPlaces = $this->nrOfHomePlaces;
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

    public function current(): SportWithNrOfFieldsAndNrOfCycles|null
    {
        return $this->current;
    }

    public function key(): string
    {
        throw new \Exception('has no key');
    }

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

    public function rewind(): void
    {
        $this->rewindNrOfHomePlaces();
        $this->current = $this->createSport();
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }

    protected function createSport(): SportWithNrOfFieldsAndNrOfCycles
    {
        if ($this->nrOfHomePlaces === 1 && $this->nrOfAwayPlaces == 1) {
            return new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), $this->nrOfFields, $this->nrOfCycles);
        } else if ($this->nrOfHomePlaces === 1 && $this->nrOfAwayPlaces == 2) {
            return new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsTwo(), $this->nrOfFields, $this->nrOfCycles);
        } else if ($this->nrOfHomePlaces === 2 && $this->nrOfAwayPlaces == 2) {
            return new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), $this->nrOfFields, $this->nrOfCycles);
        }
        throw new \Exception('unknown homeawaycombination');
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
            return $this->incrementNrOfAwayPlaces();
        }
        $this->nrOfFields++;
        $this->rewindNrOfCycles();
        return true;
    }

    protected function incrementNrOfAwayPlaces(): bool
    {
        if ($this->nrOfAwayPlaces === $this->sidePlacesRange->getMax()) {
            return $this->incrementNrOfHomePlaces();
        }
        $this->nrOfAwayPlaces++;
        $this->rewindNrOfFields();
        return true;
    }

    protected function incrementNrOfHomePlaces(): bool
    {
        if ($this->nrOfHomePlaces === $this->sidePlacesRange->getMax()) {
            return false;
        }
        $this->nrOfHomePlaces++;
        $this->rewindNrOfAwayPlaces();
        return true;
    }


}
