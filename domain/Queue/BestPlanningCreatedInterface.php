<?php

namespace SportsScheduler\Queue;

use SportsPlanning\PlanningConfiguration;

interface BestPlanningCreatedInterface
{
    public function bestPlanningCreated(PlanningConfiguration $planningConfig): void;
}