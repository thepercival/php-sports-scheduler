<?php declare(strict_types = 1);

namespace SportsScheduler\Combinations;

use drupol\phpermutations\Generators\Combinations;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DrupolCombinationGenerator extends Combinations
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
        parent::__construct(array_values($dataset), $length);
    }
}