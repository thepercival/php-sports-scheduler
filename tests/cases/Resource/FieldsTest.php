<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Input;
use SportsPlanning\PlanningConfiguration;
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
        $config = new PlanningConfiguration(
            new PouleStructure(2),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $planning = $this->createPlanning($config);
        $fields = new Fields($planning);

        $sport = $planning->getSport(1);
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
        $config = new PlanningConfiguration(
            new PouleStructure(4),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $planning = $this->createPlanning($config);
        $fields = new Fields($planning);
        self::assertCount(2, $fields->getAssignableFields($planning->getSport(2)));
        self::assertCount(2, $fields->getAssignableFields($planning->getSport(1)));
    }

    public function testSixPoulesTwoFields(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(2, 2);
        $configuration = $this->createConfiguration([2,2,2,2,2,2]);
        $planning = $this->createPlanning($configuration,$nrOfGamesPerBatchRange);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $fields = new Fields($planning);
        $games = $planning->getGames();
        $nrOfGames = count($games);
        $lastGame = $games[$nrOfGames > 0 ? $nrOfGames - 1 : 0];
        self::assertInstanceOf(AgainstGame::class, $lastGame);
        $fields->assignToGame($lastGame);

        self::assertFalse($fields->isSomeFieldAssignable(1, $planning->getPoule(6)));
    }
}
