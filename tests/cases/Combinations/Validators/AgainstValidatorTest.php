<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\Validators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\PlanningOutput\Extra;
use SportsPlanning\Sports\SportWithNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Combinations\Validators\AgainstValidator;
use SportsScheduler\Game\PlannableGameCreator as GameCreator;
use SportsPlanning\Planning;
use SportsScheduler\Schedules\CycleCreator;
use SportsScheduler\TestHelper\PlanningCreator;

class AgainstValidatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];

        $nrOfPlaces = 2;
        $input = $this->createInput([$nrOfPlaces], $sportsWithNrOfFieldsAndNrOfCycles);
        $planning = $this->createPlanning($input, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        foreach($planning->getGames() as $game) {
            $validator->addGame($game);
        }
        self::assertTrue($validator->balanced());
    }

    public function test4Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];

        $nrOfPlaces = 4;
        $input = $this->createInput([$nrOfPlaces], $sportsWithNrOfFieldsAndNrOfCycles);
        $planning = $this->createPlanning($input, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        foreach($planning->getGames() as $game) {
            $validator->addGame($game);
        }
        self::assertTrue($validator->balanced());
    }

    public function test5Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];

        $nrOfPlaces = 5;
        $input = $this->createInput([$nrOfPlaces], $sportsWithNrOfFieldsAndNrOfCycles);
        $planning = $this->createPlanning($input, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        foreach($planning->getGames() as $game) {
            $validator->addGame($game);
        }
        self::assertTrue($validator->balanced());
    }

    public function test6Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];

        $nrOfPlaces = 6;
        $input = $this->createInput([$nrOfPlaces], $sportsWithNrOfFieldsAndNrOfCycles);
        $planning = $this->createPlanning($input, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        foreach($planning->getGames() as $game) {
            $validator->addGame($game);
        }
        self::assertTrue($validator->balanced());
    }

    public function test5Places2VS2(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1)
        ];

        $nrOfPlaces = 5;
        $input = $this->createInput([$nrOfPlaces], $sportsWithNrOfFieldsAndNrOfCycles);
        $planning = $this->createPlanning($input, new SportRange(1, 1));

        $validator = new AgainstValidator($nrOfPlaces);
        $validator->addGames($planning);

        $extras = Extra::Input->value + Extra::Games->value + Extra::Totals->value;
        (new PlanningOutput())->output($planning, $extras);

        foreach($planning->getGames() as $game) {
            $validator->addGame($game);
        }
        self::assertTrue($validator->balanced());
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
//        $counter = new AgainstAndAgainstCounter($input->getPoule(1), $input->getSport(1));
//        $counter->addGames($planning);
//        echo $counter;
//
//        self::assertTrue($counter->balanced());
//    }
}
