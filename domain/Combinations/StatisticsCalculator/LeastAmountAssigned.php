<?php

namespace SportsScheduler\Combinations\StatisticsCalculator;

class LeastAmountAssigned
{
    public function __construct(public int $amount, public int $nrOfPlaces)
    {
    }
}