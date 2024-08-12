<?php

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

class LeastAmountAssigned
{
    public function __construct(public int $amount, public int $nrOfPlaces)
    {
    }
}