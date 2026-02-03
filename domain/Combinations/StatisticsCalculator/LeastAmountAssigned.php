<?php

namespace SportsScheduler\Combinations\StatisticsCalculator;

final class LeastAmountAssigned
{
    public function __construct(public int $amount, public int $nrOfPlaces)
    {
    }
}