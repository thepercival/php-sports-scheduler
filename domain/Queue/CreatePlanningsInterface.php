<?php

namespace SportsScheduler\Queue;

use SportsPlanning\PlanningConfiguration;

interface CreatePlanningsInterface
{
    public function createPlannings(
        PlanningConfiguration $planningConfig, int|null $priority = null): void;
}