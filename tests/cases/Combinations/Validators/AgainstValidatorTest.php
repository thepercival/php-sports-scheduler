<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\Validators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Combinations\Validators\AgainstValidator;
use SportsScheduler\TestHelper\PlanningCreator;

final class AgainstValidatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 2;
        $configuration = new PlanningConfiguration(
            new PouleStructure([$nrOfPlaces]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        $validator->addGames($planning);
        self::assertTrue($validator->balanced());
    }

    public function test4Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 4;
        $configuration = new PlanningConfiguration(
            new PouleStructure([$nrOfPlaces]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        $validator->addGames($planning);
        self::assertTrue($validator->balanced());
    }

    public function test5Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 5;
        $configuration = new PlanningConfiguration(
            new PouleStructure([$nrOfPlaces]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        foreach($this->getAgainstGames($planning) as $game) {
            $validator->addGame($planning, $game);
        }
        self::assertTrue($validator->balanced());
    }

    public function test6Places1VS1(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $nrOfPlaces = 6;
        $configuration = new PlanningConfiguration(
            new PouleStructure([$nrOfPlaces]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(1, 1));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $validator = new AgainstValidator($nrOfPlaces);
        $validator->addGames($planning);
        self::assertTrue($validator->balanced());
    }

    public function test5Places2VS2(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1)
        ];
        $nrOfPlaces = 5;
        $configuration = new PlanningConfiguration(
            new PouleStructure([$nrOfPlaces]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(1, 1));

        $validator = new AgainstValidator($nrOfPlaces);
        $validator->addGames($planning);

//        $extras = Extra::Input->value + Extra::Games->value + Extra::Totals->value;
//        (new PlanningOutput())->output($planning, $extras);

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
