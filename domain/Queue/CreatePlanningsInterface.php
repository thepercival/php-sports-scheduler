<?php

namespace SportsScheduler\Queue;

use SportsPlanning\Input\Configuration;
use SportsPlanning\Planning;

interface CreatePlanningsInterface
{
    public function createPlannings(Configuration $inputConfiguration, int|null $priority = null): void;
}