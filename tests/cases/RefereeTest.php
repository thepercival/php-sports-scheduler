<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Referee;
use SportsPlanning\Input;
use SportsScheduler\TestHelper\PlanningCreator;

class RefereeTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $input = new Input( $this->createConfiguration([3]) );

        $referee = new Referee($input);
        $referee->setPriority(2);
        self::assertSame($input, $referee->getInput());
        self::assertSame(3, $referee->getNumber());
        self::assertSame(2, $referee->getPriority());
    }
}
