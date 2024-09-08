<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsScheduler\Combinations\DuoPlaceNrIterator;

class DuoPlaceNrIteratorTest extends TestCase
{
    public function testNrOfPlacesIs2(): void
    {
        $duoPlaceNrIterator = new DuoPlaceNrIterator(new SportRange(1, 2));
        self::assertTrue($duoPlaceNrIterator->valid());
        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 2' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();
        self::assertNull($duoPlaceNrIterator->current());
    }

    public function testNrOfPlacesIs3(): void
    {
        $duoPlaceNrIterator = new DuoPlaceNrIterator(new SportRange(1, 3));
        self::assertTrue($duoPlaceNrIterator->valid());

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 2' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 3' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('2 & 3' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        self::assertNull($duoPlaceNrIterator->current());
    }

    public function testNrOfPlacesIs4(): void
    {
        $duoPlaceNrIterator = new DuoPlaceNrIterator(new SportRange(1, 4));
        self::assertTrue($duoPlaceNrIterator->valid());

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 2' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 3' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('1 & 4' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('2 & 3' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('2 & 4' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        $currentIt = $duoPlaceNrIterator->current();
        self::assertSame('3 & 4' , $currentIt->getIndex());
        $duoPlaceNrIterator->next();

        self::assertNull($duoPlaceNrIterator->current());
    }
}
