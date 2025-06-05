<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\Validators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Input;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Combinations\Validators\TogetherValidator;
use SportsScheduler\Game\PlannableGameCreator as GameCreator;
use SportsPlanning\Planning;
use SportsScheduler\Schedules\CycleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class TogetherValidatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 2;
        $config = $this->createConfiguration(
            [$nrOfPlaces],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $planning = $this->createPlanning($config, new SportRange(1, 1));

        $counter = new TogetherValidator($nrOfPlaces);
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test4Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 4;
        $config = $this->createConfiguration(
            [$nrOfPlaces],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $planning = $this->createPlanning($config, new SportRange(1, 1));

        $counter = new TogetherValidator($nrOfPlaces);
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 5;
        $config = $this->createConfiguration(
            [$nrOfPlaces],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $planning = $this->createPlanning($config, new SportRange(1, 1));

        $counter = new TogetherValidator($nrOfPlaces);
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test6Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 6;
        $config = $this->createConfiguration(
            [$nrOfPlaces],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $planning = $this->createPlanning($config, new SportRange(1, 1));

        $counter = new TogetherValidator($nrOfPlaces);
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places2VS2(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1)
        ];
        $nrOfPlaces = 5;
        $config = $this->createConfiguration(
            [$nrOfPlaces],
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo()
        );
        $planning = $this->createPlanning($config, new SportRange(1, 1));

        $counter = new TogetherValidator($nrOfPlaces);
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

//    public function test6Places2VS2(): void
//    {
//        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 8);
//        $input = $this->createInput([6], [$sportVariant]);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $counter = new WithAndAgainstCounter($input->getPoule(1), $input->getSport(1));
//        $counter->addGames($planning);
//        echo $counter;
//
//        self::assertTrue($counter->balanced());
//    }
}
