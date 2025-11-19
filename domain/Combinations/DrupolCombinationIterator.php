<?php declare(strict_types = 1);

namespace SportsScheduler\Combinations;

use drupol\phpermutations\Iterators\Combinations;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DrupolCombinationIterator extends Combinations
{

    /**
     * Combinations constructor.
     *
     * @param array<int, mixed> $dataset
     *                          The dataset
     * @param int|null $length
     *                          The length
     */
    public function __construct(array $dataset = [], $length = null)
    {
        parent::__construct($dataset, $length);
    }
}