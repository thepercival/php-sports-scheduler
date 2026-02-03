<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations;

use function count;

/**
 * @psalm-template T
 */
abstract class Combinatorics
{
    /**
     * @var list<T>
     */
    protected array $dataset;

    protected int $datasetCount;

    protected int $length;

    /**
     * @param list<T> $dataset
     * @param int|null $length
     */
    public function __construct(array $dataset = [], int|null $length = null)
    {
        $this->setDataset($dataset);
        $this->datasetCount = count($this->dataset);
        $this->setLength($length);
    }

    public function count(): int
    {
        return count($this->createEmptyDataset());
    }

    /**
     * @return array<int, T>
     */
    public function getDataset(): array
    {
        return $this->dataset;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param list<T> $dataset
     * @return self
     */
    public function setDataset(array $dataset = []): self
    {
        $this->dataset = $dataset;

        return $this;
    }

    /**
     * @param int|null $length
     * @return self
     */
    public function setLength(int|null $length = null): self
    {
        $length = $length ?? $this->datasetCount;
        $this->length = (abs($length) > $this->datasetCount) ? $this->datasetCount : $length;

        return $this;
    }

    /**
     * @return list<T>
     */
    public function createEmptyDataset(): array
    {
        return [];
    }

    protected function fact(int $n, int $total = 1): int
    {
        return (2 > $n) ? $total : $this->fact($n - 1, $total * $n);
    }
}
