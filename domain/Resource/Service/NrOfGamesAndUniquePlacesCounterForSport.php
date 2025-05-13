<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\Service;

use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsScheduler\Resource\UniquePlacesCounter;

class NrOfGamesAndUniquePlacesCounterForSport
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, UniquePlacesCounter> $uniquePlacesCounters
     */
    protected array $uniquePlacesCounterMap = [];

    public function __construct(
        public readonly SportWithNrOfFields $sportWithNrOfFields
    )
    {
    }


    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->nrOfGames++;
        $pouleNr = $game->getPoule()->getNumber();
        if (!array_key_exists($pouleNr, $this->uniquePlacesCounterMap)) {
            $this->uniquePlacesCounterMap[$pouleNr] = new UniquePlacesCounter($game->getPoule());
        }
        $this->uniquePlacesCounterMap[$pouleNr]->addGame($game);
    }

    /**
     * @return array<int, UniquePlacesCounter>
     */
    public function getUniquePlacesCounterMap(): array
    {
        return $this->uniquePlacesCounterMap;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}
