<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Referee;
use SportsScheduler\TestHelper\PlanningCreator;

final class RefereeTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $referee = new Referee(1);
        $referee->setPriority(2);
        self::assertSame(1, $referee->refereeNr);
        self::assertSame(2, $referee->getPriority());
    }
}
