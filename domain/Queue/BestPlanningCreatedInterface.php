<?php

namespace SportsScheduler\Queue;

use SportsPlanning\Input\Configuration;
use SportsPlanning\Planning;

interface BestPlanningCreatedInterface
{
    public function bestPlanningCreated(Planning $bestPlanning): void;
}