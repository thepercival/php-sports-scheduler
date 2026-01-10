<?php

declare(strict_types=1);

namespace SportsScheduler\Permutations\Iterators;

use SportsScheduler\Permutations\IteratorTemplate;

/**
 * @psalm-template T
 * @template-extends IteratorTemplate<T>
 */
class CombinationsIterator extends IteratorTemplate
{
    /**
     * @var array<int, int>
     */
    protected array $rangeAfterRewind = [];

    /**
     * @param list<T> $dataset
     * @param int|null $length
     */
    public function __construct(array $dataset = [], $length = null)
    {
        parent::__construct($dataset, $length);
        $this->rewind();
    }

    #[\Override]
    public function count(): int
    {
        $i = 0;

        for ($this->rewind(); $this->valid(); $this->next()) {
            ++$i;
        }

        return $i;
    }

    /**
     * @return list<T>
     */
    public function customCurrent(): array
    {
        $r = [];

        for ($i = 0; $i < $this->length; ++$i) {
            $r[] = $this->dataset[$this->rangeAfterRewind[$i]];
        }

        return $r;
    }

    /**
     * @return T
     */
    #[\Override]
    public function current(): mixed
    {
        throw new \Exception('unuable');
    }

    #[\Override]
    public function next(): void
    {
        if ($this->nextHelper()) {
            ++$this->key;
        } else {
            $this->key = -1;
        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->rangeAfterRewind = range(0, $this->length);
        $this->key = 0;
    }

    #[\Override]
    public function valid(): bool
    {
        return 0 <= $this->key;
    }

    private function nextHelper(): bool
    {
        $i = $this->length - 1;

        while (0 <= $i && $this->datasetCount - $this->length + $i === $this->rangeAfterRewind[$i]) {
            --$i;
        }

        if (0 > $i) {
            return false;
        }

        ++$this->rangeAfterRewind[$i];

        while ($this->length - 1 > $i++) {
            $this->rangeAfterRewind[$i] = $this->rangeAfterRewind[$i - 1] + 1;
        }

        return true;
    }

    /**
     * @return list<T>
     */
    public function rewindAndExport(): array
    {
        $data = [];

        for ($this->rewind(); $this->valid(); $this->next()) {
            $data[] = $this->current();
        }
        $this->rewind();

        return $data;
    }

}
