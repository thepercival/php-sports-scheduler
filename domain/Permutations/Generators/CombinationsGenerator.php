<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations\Generators;

use SportsPlanning\Place;
use SportsScheduler\Permutations\GeneratorInterface;
use SportsScheduler\Permutations\Iterators\CombinationsIterator;
use Generator;

use function array_slice;
use function count;

/**
 * @template T
 * @template-extends CombinationsIterator<T>
 * @template-implements GeneratorInterface<T>
 */
final class CombinationsGenerator extends CombinationsIterator implements GeneratorInterface
{
//    public function generator()
//    {
//        return $this->get($this->getDataset(), $this->getLength());
//    }

    /**
     * @param array<int, T> $dataset
     * @param int   $length
     *
     * @return Generator<array>
     */
    protected function get(array $dataset, $length)
    {
        $originalLength = count($dataset);
        $remainingLength = $originalLength - $length + 1;

        for ($i = 0; $i < $remainingLength; ++$i) {
            $current = $dataset[$i];

            if (1 === $length) {
                yield [$current];
            } else {
                $remaining = array_slice($dataset, $i + 1);

                foreach ($this->get($remaining, $length - 1) as $permutation) {
                    array_unshift($permutation, $current);

                    yield $permutation;
                }
            }
        }
    }
}
