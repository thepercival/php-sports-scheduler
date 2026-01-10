<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations;

use Generator;

/**
 * @psalm-template T
 */
interface GeneratorInterface
{
//    /**
//     * @return Generator<int, T>
//     */
//    public function generator();

    /**
     * Convert the generator into an array.
     *
     * @return list<T>
     */
    public function rewindAndExport(): array;
}
