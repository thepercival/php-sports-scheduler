<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\Service;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsScheduler\Resource\Service\SimCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class SimCalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testMultipleUnknown(): void
    {
        $sportsWithNrOfFields = [
            new SportWithNrOfFields( new AgainstOneVsOne(), 2),
            new SportWithNrOfFields( new AgainstOneVsOne(), 1),
            new SportWithNrOfFields( new AgainstOneVsOne(), 1),
        ];

        $calculator = new SimCalculator();
        $maxNrOfSimultaneousGames = $calculator->calculateMaxSimNrOfGames(
            new PouleStructure([10]),
            $sportsWithNrOfFields,
            null
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(4, $maxNrOfSimultaneousGames);
    }
}
