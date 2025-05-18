<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Input;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Resource\Fields;
use SportsScheduler\TestHelper\PlanningCreator;
// use SportsScheduler\TestHelper\PlanningReplacer;

class FieldsTest extends TestCase
{
    use PlanningCreator;
    // use PlanningReplacer;

    public function testOnePouleTwoFields(): void
    {
        $refereeInfo = new PlanningRefereeInfo();
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $config = new Configuration(new PouleStructure(2), $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo, false);
        $input = new Input($config);

        $fields = new Fields($input);

        $sport = $input->getSport(1);
        self::assertCount(2, $fields->getAssignableFields($sport));
    }

    //    protected function getAgainstGppSportVariantWithFields(
//        int $nrOfFields,
//        int $nrOfHomePlaces = 1,
//        int $nrOfAwayPlaces = 1,
//        int $nrOfGamesPerPlace = 1
//    ): SportVariantWithFields {
//        return new SportVariantWithFields(
//            $this->getAgainstGppSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace),
//            $nrOfFields
//        );
//    }

    public function testMultipleSports(): void
    {
        $refereeInfo = new PlanningRefereeInfo();
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $config = new Configuration(new PouleStructure(4), $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo, false);
        $input = new Input($config);
        $fields = new Fields($input);
        self::assertCount(2, $fields->getAssignableFields($input->getSport(2)));
        self::assertCount(2, $fields->getAssignableFields($input->getSport(1)));
    }

    public function testSixPoulesTwoFields(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(2, 2);
        $configuration = $this->createConfiguration([2,2,2,2,2,2]);
        $planning = $this->createPlanning($configuration,$nrOfGamesPerBatchRange);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $fields = new Fields($planning->getInput());
        $lastGame = $planning->getAgainstGames()->last();
        self::assertInstanceOf(AgainstGame::class, $lastGame);
        $fields->assignToGame($lastGame);

        $sport = $planning->getInput()->getSport(1);
        self::assertFalse($fields->isSomeFieldAssignable($sport, $planning->getInput()->getPoule(6)));
    }
}
