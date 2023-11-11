<?php

namespace SportsScheduler\Queue\PlanningInput;

use SportsPlanning\Input as PlanningInput;

interface CreatePlanningsInterface
{
    public function sendCreatePlannings(
        PlanningInput $input,
        string|int|null $competitionId = null,
        string|null $leagueName = null,
        int $startRoundNumber = null,
        int|null $priority = null
    ): void;
}