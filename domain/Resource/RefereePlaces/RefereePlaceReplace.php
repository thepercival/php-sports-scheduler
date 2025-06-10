<?php

namespace SportsScheduler\Resource\RefereePlaces;

use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;

final class RefereePlaceReplace
{
    public function __construct(
        protected SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        protected TogetherGame|AgainstGame $game,
        protected string $replacement,
        protected string $replaced
    ) {
    }

    public function getBatch(): SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule
    {
        return $this->batch;
    }

    public function getGame(): AgainstGame|TogetherGame
    {
        return $this->game;
    }

    public function getReplaced(): string
    {
        return $this->replaced;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }
}
