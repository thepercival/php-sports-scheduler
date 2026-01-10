<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations;

use Countable;
use Iterator;

/**
 * @psalm-template T
 * @template-extends Combinatorics<T>
 * @template-implements Iterator<int, T>
 */
abstract class IteratorTemplate extends Combinatorics implements Countable, Iterator
{
//    /**
//     * A copy of the dataset at a give time.
//     *
//     * @var array<int, T>
//     */
//    protected $current;

    protected int $key = 0;

    /**
     * @return T
     */
    #[\Override]
    abstract public function current(): mixed;

    #[\Override]
    public function key(): int
    {
        return $this->key;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->key = 0;
    }
}
