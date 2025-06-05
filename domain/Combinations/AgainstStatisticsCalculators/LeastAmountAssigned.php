<?php

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

final class LeastAmountAssigned
{
    public function __construct(public int $amount, public int $nrOfPlaces)
    {
    }
}