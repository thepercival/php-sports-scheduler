<?php

namespace SportsScheduler\Queue;

use SportsPlanning\Input\Configuration;

interface BestPlanningCreatedInterface
{
    public function bestPlanningCreated(Configuration $inputConfiguration): void;
}