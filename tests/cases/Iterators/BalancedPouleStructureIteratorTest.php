<?php

namespace SportsScheduler\Tests\Iterators;

use Exception;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsScheduler\Iterators\BalancedPouleStructureIterator;

final class BalancedPouleStructureIteratorTest extends TestCase
{
    public function testRewindNormal(): void
    {
        $placesRange = new SportRange(4, 4);
        $placesPerPouleRange = new SportRange(4, 4);
        $pouleRange = new SportRange(1, 1);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertNotNull($iterator->current());
    }

    public function testRewind(): void
    {
        $placesRange = new SportRange(10, 20);
        $placesPerPouleRange = new SportRange(4, 4);
        $pouleRange = new SportRange(4, 4);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::expectException(Exception::class);
        $iterator->rewind();
    }

    public function testNormalCase(): void
    {
        $placesRange = new SportRange(10, 20);
        $placesPerPouleRange = new SportRange(4, 5);
        $pouleRange = new SportRange(4, 5);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([4,4,4,4], $balancedPouleStructure->toArray());
        $iterator->next();
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([5,4,4,4], $balancedPouleStructure->toArray());
        $iterator->next();
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([5,5,4,4], $balancedPouleStructure->toArray());
        $iterator->next();
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([5,5,5,4], $balancedPouleStructure->toArray());
        $iterator->next();
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([5,5,5,5], $balancedPouleStructure->toArray());
        $iterator->next();
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([4,4,4,4,4], $balancedPouleStructure->toArray());
        $iterator->next();
        self::assertNull($iterator->current());
    }

    public function testNoPouleRange(): void
    {
        $placesRange = new SportRange(5, 10);
        $placesPerPouleRange = new SportRange(4, 5);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange);
        $balancedPouleStructure = $iterator->current();
        self::assertNotNull($balancedPouleStructure);
        self::assertSame([5], $balancedPouleStructure->toArray());
    }

    public function testContent(): void
    {
        $placesRange = new SportRange(10, 10);
        $placesPerPouleRange = new SportRange(5, 5);
        $pouleRange = new SportRange(2, 2);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertSame('{"poules":[5,5]}', json_encode($iterator->current()));
    }

    public function testValid(): void
    {
        $placesRange = new SportRange(10, 10);
        $placesPerPouleRange = new SportRange(5, 5);
        $pouleRange = new SportRange(2, 2);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertTrue($iterator->valid());
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testNoPossibilities(): void
    {
        $placesRange = new SportRange(10, 15);
        $placesPerPouleRange = new SportRange(4, 5);
        $pouleRange = new SportRange(4, 5);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertNull($iterator->current());
    }

    public function testNextWithNoCurrent(): void
    {
        $placesRange = new SportRange(10, 15);
        $placesPerPouleRange = new SportRange(4, 5);
        $pouleRange = new SportRange(4, 5);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertNull($iterator->current());
        $iterator->next();
        self::assertNull($iterator->current());
    }

    public function testValidateNrOfPlacesPerPouleAfterNext(): void
    {
        $placesRange = new SportRange(10, 10);
        $placesPerPouleRange = new SportRange(3, 3);
        $pouleRange = new SportRange(2, 2);
        $iterator = new BalancedPouleStructureIterator($placesRange, $placesPerPouleRange, $pouleRange);
        self::assertNull($iterator->current());
    }
}
