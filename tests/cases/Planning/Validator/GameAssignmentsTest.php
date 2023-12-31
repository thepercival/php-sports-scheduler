<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Planning\Validator;

use Exception;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Resource\ResourceType;
use SportsScheduler\Planning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\PlanningReplacer;

class GameAssignmentsTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testGetCountersFields(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));

        $resourceCounter = new ResourceCounter($planning);
        $gameCounters = $resourceCounter->getCounters(ResourceType::Fields->value);

        $fieldGameCounters = $gameCounters[ResourceType::Fields->value];
        $field = $planning->getInput()->getSport(1)->getField(1);
        $gameFieldCounter = $fieldGameCounters[$field->getUniqueIndex()];
        self::assertSame($field, $gameFieldCounter->getResource());
        self::assertSame(5, $gameFieldCounter->getNrOfGames());
    }

    public function testGetCountersReferees(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $resourceCounter = new ResourceCounter($planning);
        $gameCounters = $resourceCounter->getCounters(ResourceType::Referees->value);

        /** @var GameCounter[] $gameRefereeCounters */
        $gameRefereeCounters = $gameCounters[ResourceType::Referees->value];
        $referee = $planning->getInput()->getReferee(1);
        $gameRefereeCounter = $gameRefereeCounters[(string)$referee->getNumber()];
        self::assertSame($referee, $gameRefereeCounter->getResource());
        self::assertSame(5, $gameRefereeCounter->getNrOfGames());
    }

    public function testGetCountersRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $resourceCounter = new ResourceCounter($planning);
        $gameCounters = $resourceCounter->getCounters(ResourceType::RefereePlaces->value);

        /** @var GameCounter[] $gameRefereePlaceCounters */
        $gameRefereePlaceCounters = $gameCounters[ResourceType::RefereePlaces->value];
        $place = $planning->getInput()->getPoule(1)->getPlace(1);
        $gameRefereePlaceCounter = $gameRefereePlaceCounters[(string)$place];
        self::assertSame($place, $gameRefereePlaceCounter->getResource());
        self::assertSame(2, $gameRefereePlaceCounter->getNrOfGames());
    }

    public function testGetUnequalRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

        $firstPoule = $planning->getInput()->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
            || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
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
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules, 1));
        $planning = $this->createPlanning(
            $this->createInput([5, 4], null, $refereeInfo)
        );

        $secondPoule = $planning->getInput()->getPoule(2);
        $replacedPlace = $secondPoule->getPlace(4);
        $replacedByPlace = $secondPoule->getPlace(3);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
    }

    public function testValidateUnequalFields(): void
    {
        $sportVariant = $this->getAgainstH2hSportVariantWithFields(2);
        $planning = $this->createPlanning(
            $this->createInput([5], [$sportVariant])
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedField = $planning->getInput()->getSport(1)->getField(2);
        $replacedByField = $planning->getInput()->getSport(1)->getField(1);
        $this->replaceField($planning->createFirstBatch(), $replacedField, $replacedByField);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalReferees(): void
    {
        $refereeInfo = new RefereeInfo(3);
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedReferee = $planning->getInput()->getReferee(2);
        $replacedByReferee = $planning->getInput()->getReferee(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $firstBatch);
        $this->replaceReferee($firstBatch, $replacedReferee, $replacedByReferee);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule, 1));
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

        $firstPoule = $planning->getInput()->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testEquallyAssignedFieldsMultipleSport(): void
    {
        $sportVariant1 = $this->getAgainstGppSportVariantWithFields(4, 1, 1, 4);
        $sportVariant2 = $this->getAgainstGppSportVariantWithFields(1, 1, 1, 4);
        $planning = $this->createPlanning(
            $this->createInput([5], [$sportVariant1, $sportVariant2])
        );

        $validator = new GameAssignmentValidator($planning);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }

    public function testValidate(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule, 1));
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );
//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);
        $validator = new GameAssignmentValidator($planning);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }
}
