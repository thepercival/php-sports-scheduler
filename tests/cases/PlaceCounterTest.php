<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForPlace;
use SportsScheduler\TestHelper\PlanningCreator;

class PlaceCounterTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));
        $place = $planning->getInput()->getPoule(1)->getPlace(1);
        $placeCounter = new CounterForPlace($place);
        self::assertSame(1, $placeCounter->getPlaceNr());
    }

    public function testCounter(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));
        $place = $planning->getInput()->getPoule(1)->getPlace(1);
        $placeCounter = new CounterForPlace($place);
        $placeCounter = $placeCounter->increment();
        $placeCounter = $placeCounter->increment();
        $placeCounter = $placeCounter->increment();
        self::assertCount(3, $placeCounter);
    }
}
