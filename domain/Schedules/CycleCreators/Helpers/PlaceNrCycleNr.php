<?php

namespace SportsScheduler\Schedules\CycleCreators\Helpers;

final class PlaceNrCycleNr
{
    public function __construct(
        public int $placeNr,
        public int $cycleNr)
    {
    }
}