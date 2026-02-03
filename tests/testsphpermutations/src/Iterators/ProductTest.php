<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations\Tests\Iterators;

use SportsScheduler\Permutations\Iterators\Product;
use SportsScheduler\Permutations\Tests\AbstractTest;

/**
 * Class ProductTest.
 *
 * @internal
 * @covers \drupol\phpermutations\Iterators\Product
 */
final class ProductTest extends AbstractTest
{
    /**
     * The type.
     */
    public const TYPE = 'product';

    /**
     * The tests.
     *
     * @dataProvider dataProvider
     *
     * @param mixed $input
     * @param mixed $expected
     */
    public function testProduct($input, $expected)
    {
        $product = new Product($input['dataset']);

        self::assertSame($input['dataset'], $product->getDataset());
        self::assertEquals(
            $expected['dataset'],
            $product->toArray(),
            '$canonicalize = true',
            $delta = 0.0,
            $maxDepth = 10,
            $canonicalize = true
        );
        self::assertSame($expected['count'], $product->count());
    }
}
