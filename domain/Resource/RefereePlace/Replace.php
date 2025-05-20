<?php

namespace SportsScheduler\Resource\RefereePlace;

use SportsPlanning\Batches\SelfRefereeBatchOtherPoule;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;

class Replace
{
    public function __construct(
        protected SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        protected TogetherGame|AgainstGame $game,
        protected Place $replacement,
        protected Place $replaced
    ) {
    }

    public function getBatch(): SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule
    {
        return $this->batch;
    }

    public function getGame(): AgainstGame|TogetherGame
    {
        return $this->game;
    }

    public function getReplaced(): Place
    {
        return $this->replaced;
    }

    public function getReplacement(): Place
    {
        return $this->replacement;
    }
}
