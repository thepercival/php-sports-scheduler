<?php

namespace SportsScheduler\Schedules\CycleCreators\Helpers;

class PlaceNrCycleNr
{
    public function __construct(
        public int $placeNr,
        public int $cycleNr)
    {
    }
}