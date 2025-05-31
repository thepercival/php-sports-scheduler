<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Batches\Batch;
use SportsPlanning\Referee;

class RefereeService
{
    /**
     * @param list<Referee> $referees
     */
    public function __construct(private array $referees)
    {
    }


    public function assign(Batch $batch): void
    {
        $this->assignBatch($batch->getFirst(), $this->referees);
    }

    /**
     * @param Batch $batch
     * @param list<Referee> $referees
     */
    protected function assignBatch(Batch $batch, array $referees): void
    {
        foreach ($batch->getGames() as $game) {
            $referee = array_shift($referees);
            if ($referee === null) {
                break;
            }
            $game->setRefereeNr($referee->refereeNr);
            array_push($referees, $referee);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->assignBatch($nextBatch, $referees);
        }
    }
}
