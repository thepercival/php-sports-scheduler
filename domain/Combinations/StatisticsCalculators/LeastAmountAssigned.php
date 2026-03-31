<?php

namespace SportsScheduler\Combinations\StatisticsCalculators;

final class LeastAmountAssigned
{
    public function __construct(public int $amount, public int $nrOfPlaces)
    {
    }
}