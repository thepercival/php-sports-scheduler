<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceNrCounter;

final class PlaceNrCounterTest extends TestCase
{
    public function testSimple(): void
    {
        $placeNr = 1;
        $placeNrCounter = new PlaceNrCounter($placeNr);
        self::assertSame(1, $placeNrCounter->getPlaceNr());
    }

    public function testCounter(): void
    {
        $placeNr = 1;
        $placeNrCounter = new PlaceNrCounter($placeNr);
        $placeNrCounter->increment();
        $placeNrCounter->increment();
        $placeNrCounter->increment();
        self::assertCount(3, $placeNrCounter);
    }
}
