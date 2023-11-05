<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\Service;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsPlanning\Output\Planning as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Resource\Service\InfoToAssign;
use SportsScheduler\Resource\Service\SimCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class SimCalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testMultipleUnknown(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
        ];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::Disabled));
        $input = $this->createInput([10], $sportVariantsWithFields, $refereeInfo);
        $planning = $this->createPlanning($input, new SportRange(3, 3), 0, true, false, null, 6);

        $calculator = new SimCalculator($input);
        $infoToAssign = new InfoToAssign($planning->getGames());
        $maxNrOfSimultaneousGames = $calculator->getMaxNrOfGamesPerBatch($infoToAssign);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(4, $maxNrOfSimultaneousGames);
    }
}
