<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Poule;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsScheduler\Resource\UniquePlacesCounter;

final class NrOfGamesAndUniquePlacesCounterForSport
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, UniquePlacesCounter> $uniquePlacesCounters
     */
    protected array $uniquePlacesCounterMap = [];

    /**
     * @param array<int, Poule> $pouleMap
     * @param SportWithNrOfFields $sportWithNrOfFields
     */
    public function __construct(
        public readonly array $pouleMap,
        public readonly SportWithNrOfFields $sportWithNrOfFields
    )
    {
    }


    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->nrOfGames++;
        $pouleNr = $game->pouleNr;
        if (!array_key_exists($pouleNr, $this->uniquePlacesCounterMap)) {
            if (array_key_exists($pouleNr, $this->pouleMap)) {
                $this->uniquePlacesCounterMap[$pouleNr] = new UniquePlacesCounter($this->pouleMap[$pouleNr]);
            }
        }
        $this->uniquePlacesCounterMap[$pouleNr]->addGame($game);
    }

    /**
     * @return list<UniquePlacesCounter>
     */
    public function getUniquePlacesCounters(): array
    {
        return array_values($this->uniquePlacesCounterMap);
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}
