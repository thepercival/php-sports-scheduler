<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Planning\Validator;

use Exception;
use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Resource\ResourceType;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Planning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\PlanningReplacer;

final class GameAssignmentsTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testGetCountersFields(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();

        $resourceCounter = new ResourceCounter($planningWithMeta);
        $gameCounters = $resourceCounter->getCounters(ResourceType::Fields->value);

        $fieldGameCounters = $gameCounters[ResourceType::Fields->value];
        $field = $planning->getSport(1)->getField(1);
        $gameFieldCounter = $fieldGameCounters[$field->getUniqueIndex()];
        self::assertSame($field, $gameFieldCounter->getResource());
        self::assertSame(5, $gameFieldCounter->getNrOfGames());
    }

    public function testGetCountersReferees(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $resourceCounter = new ResourceCounter($planningWithMeta);
        $gameCounters = $resourceCounter->getCounters(ResourceType::Referees->value);

        /** @var GameCounter[] $gameCountersForReferee */
        $gameCountersForReferee = $gameCounters[ResourceType::Referees->value];
        $referee = $planning->getReferee(1);
        $gameRefereeCounter = $gameCountersForReferee[$referee->getUniqueIndex()];
        self::assertSame($referee, $gameRefereeCounter->getResource());
        self::assertSame(5, $gameRefereeCounter->getNrOfGames());
    }

    public function testGetCountersRefereePlaces(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $resourceCounter = new ResourceCounter($planningWithMeta);
        $gameCounters = $resourceCounter->getCounters(ResourceType::RefereePlaces->value);

        /** @var GameCounter[] $gameRefereePlaceCounters */
        $gameRefereePlaceCounters = $gameCounters[ResourceType::RefereePlaces->value];
        $place = $planning->getPoule(1)->getPlace(1);
        $gameRefereePlaceCounter = $gameRefereePlaceCounters[$place->getUniqueIndex()];
        self::assertSame($place, $gameRefereePlaceCounter->getResource());
        self::assertSame(2, $gameRefereePlaceCounter->getNrOfGames());
    }

    public function testGetUnequalRefereePlaces(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $selfReferee = SelfReferee::SamePoule;
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo($selfReferee));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();

        $firstPoule = $planning->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planningWithMeta->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoules
            || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $selfReferee === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planningWithMeta);
        $unequals = $validator->getRefereePlaceUnequals();


        self::assertCount(1, $unequals);
        $firstUnequal = reset($unequals);
        self::assertNotFalse($firstUnequal);
        $minGameCounters = $firstUnequal->getMinGameCounters();
        $maxGameCounters = $firstUnequal->getMaxGameCounters();

        self::assertSame(2, $firstUnequal->getDifference());
        self::assertCount(1, $minGameCounters);
        self::assertCount(1, $maxGameCounters);

        /** @var GameCounter $minGameCounter */
        $minGameCounter = reset($minGameCounters);
        /** @var GameCounter $maxGameCounter */
        $maxGameCounter = reset($maxGameCounters);
        self::assertSame($replacedPlace, $minGameCounter->getResource());
        self::assertSame($replacedByPlace, $maxGameCounter->getResource());
        self::assertSame(1, $minGameCounter->getNrOfGames());
        self::assertSame(3, $maxGameCounter->getNrOfGames());
    }

    public function testValidateRefereePlacesTwoPoulesNotEqualySized(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules, 1));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5, 4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();


        $secondPoule = $planning->getPoule(2);
        $replacedPlace = $secondPoule->getPlace(4);
        $replacedByPlace = $secondPoule->getPlace(3);
        $firstBatch = $planningWithMeta->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoules
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $refereeInfo->selfRefereeInfo?->selfReferee === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planningWithMeta);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
    }

    public function testValidateUnequalFields(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);
        $planning = $planningWithMeta->getPlanning();

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedField = $planning->getSport(1)->getField(2);
        $replacedByField = $planning->getSport(1)->getField(1);
        $this->replaceField($planningWithMeta->createFirstBatch(), $replacedField, $replacedByField);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planningWithMeta);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalReferees(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(3),
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedReferee = $planningWithMeta->getPlanning()->getReferee(2);
        $replacedByReferee = $planningWithMeta->getPlanning()->getReferee(1);
        $firstBatch = $planningWithMeta->createFirstBatch();
        self::assertInstanceOf(Batch::class, $firstBatch);
        $this->replaceReferee($firstBatch, $replacedReferee, $replacedByReferee);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planningWithMeta);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalRefereePlaces(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $selfReferee = SelfReferee::SamePoule;
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo($selfReferee, 1));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);

        $firstPoule = $planningWithMeta->getPlanning()->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planningWithMeta->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoules
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $selfReferee === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planningWithMeta);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testEquallyAssignedFieldsMultipleSport(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 4, 4),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 4)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);

        $validator = new GameAssignmentValidator($planningWithMeta);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }

    public function testValidate(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule, 1));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false
        );
        $orchestration = new PlanningOrchestration($configuration);
        $planningWithMeta = $this->createPlanningWithMeta($orchestration);

        $validator = new GameAssignmentValidator($planningWithMeta);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }
}
