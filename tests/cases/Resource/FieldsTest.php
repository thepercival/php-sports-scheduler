<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Resource\Fields;
use SportsScheduler\TestHelper\PlanningCreator;
// use SportsScheduler\TestHelper\PlanningReplacer;

final class FieldsTest extends TestCase
{
    use PlanningCreator;
    // use PlanningReplacer;

    public function testOnePouleTwoFields(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planning = Planning::fromConfiguration($configuration);
        $fields = new Fields($configuration, $planning);

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
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planning = Planning::fromConfiguration($configuration);
        $fields = new Fields($configuration, $planning);
        self::assertCount(2, $fields->getAssignableFields($planning->getSport(2)));
        self::assertCount(2, $fields->getAssignableFields($planning->getSport(1)));
    }

    public function testSixPoulesTwoFields(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(2, 2);

        $sportWithNrOfFieldsAndNrOfCycles = [new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)];
        $refereeInfo = RefereeInfo::fromNrOfReferees(2);
        $configuration = new PlanningConfiguration(
            new PouleStructure([2,2,2,2,2,2]),
            $sportWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration, $nrOfGamesPerBatchRange);

        $fields = new Fields($configuration, $planningWithMeta->getPlanning());
        $games = $planningWithMeta->getPlanning()->getGames();
        $nrOfGames = count($games);
        $lastGame = $games[$nrOfGames > 0 ? $nrOfGames - 1 : 0];
        self::assertInstanceOf(AgainstGame::class, $lastGame);
        $fields->assignToGame($lastGame);

        self::assertFalse($fields->isSomeFieldAssignable(1, 6));
    }
}
