<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Planning;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Output\PlanningOutput;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsScheduler\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Validity;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Resource\RefereePlace\Service as RefereePlaceService;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\PlanningReplacer;

class ValidatorTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;
    use GppMarginCalculator;

    public function testHasEnoughTotalNrOfGames(): void
    {
        $planning = new Planning($this->createInput([3,3]), new SportRange(1, 1), 1);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::NO_GAMES, $validity & Validity::NO_GAMES);
    }

    public function testHasEmptyGamePlace(): void
    {
        $sportVariant = $this->getAgainstGppSportVariantWithFields(2, 2, 2, 3);
        $planning = $this->createPlanning($this->createInput([5], [$sportVariant]));
        $firstGame = $planning->getAgainstGames()->first();
        self::assertNotFalse($firstGame);
        $firstGame->getPlaces()->clear();

        //(new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::EMPTY_PLACE, $validity & Validity::EMPTY_PLACE);
    }

    public function testHasEmptyGameRefereePlace(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // --------- BEGIN EDITING --------------
        /** @var AgainstGame $firstGame */
        $firstGame = $planning->getAgainstGames()->first();
        $firstGame->setRefereePlace(null);
//        $firstBatch = $planning->createFirstBatch();
//        $firstBatch->removeAsReferee( $firstGame->getRefereePlace()/*, $firstGame*/ );
        // --------- BEGIN EDITING --------------
        //(new PlanningOutput())->outputWithGames($planning, true);

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::EMPTY_REFEREEPLACE,
            $validity & Validity::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5])
        );

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getAgainstGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::EMPTY_REFEREE, $validity & Validity::EMPTY_REFEREE);
    }

    public function testAllPlacesSameNrOfGames(): void
    {
        $refereeInfo = new RefereeInfo();
        $input = $this->createInput([5], null, $refereeInfo);
        $planning = new Planning($input, new SportRange(1, 1), 1);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $planningValidator = new PlanningValidator();

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getAgainstGames()->first();
        $planning->getAgainstGames()->removeElement($planningGame);

//        (new PlanningOutput())->output($planning, PlanningOutput\Extra::Games->value);

        self::assertSame(Validity::UNEQUAL_GAME_AGAINST, $planningValidator->validate($planning));
    }

    public function testGamesInARow(): void
    {
        $planning = $this->createPlanning($this->createInput([4]), null);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);

        // (new PlanningOutput())->output($planning, PlanningOutput\Extra::Games);

        // ---------------- MAKE INVALID --------------------- //
        $refObject   = new ReflectionObject($planning);
        $refProperty = $refObject->getProperty('maxNrOfGamesInARow');
        // $refProperty->setAccessible(true);
        $refProperty->setValue($planning, 1);
        // ---------------- MAKE INVALID --------------------- //

//        (new PlanningOutput())->outputWithGames($planning, true);


        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::TOO_MANY_GAMES_IN_A_ROW,
            $validity & Validity::TOO_MANY_GAMES_IN_A_ROW
        );
    }

    public function testGameUnequalHomeAway(): void
    {
        $planning = $this->createPlanning($this->createInput([2]));

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::Home)->first();
        // $firstHomePlace = $firstHomeGamePlace->getPlace();
        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        $planningGame->getPlaces()->add($firstHomeGamePlace);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::UNEQUAL_GAME_HOME_AWAY,
            $validity & Validity::UNEQUAL_GAME_HOME_AWAY
        );
    }

    public function testBatchMultipleFields(): void
    {
        $planning = $this->createPlanning($this->createInput([5]), new SportRange(2, 2));

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getInput()->getSport(1)->getField($newFieldNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH,
            Validity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH & $validity
        );
    }


    public function testBatchMultipleReferees(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([4]),
            new SportRange(2, 2)
        );

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $referee = $planningGame->getReferee();
        self::assertNotNull($referee);
        $newRefereeNr = $referee->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getInput()->getReferee($newRefereeNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH,
            Validity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH & $validity
        );
    }

    public function testValidResourcesPerBatch(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5])
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(3);
        $planning = $this->createPlanning($this->createInput([4], [$sportVariantWithFields]));

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->getNumber() === 3 ? 1 : 3;
        $planningGame->setField($planning->getInput()->getSport(1)->getField($newFieldNr));

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::UNEQUALLY_ASSIGNED_FIELDS,
            $validity & Validity::UNEQUALLY_ASSIGNED_FIELDS
        );
    }
    public function testValidatePerPouleTooMuchCompetitors(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(6);
        $planning = $this->createPlanning(
            $this->createInput(
                [8,8,8],
                [$sportVariantWithFields],
                new RefereeInfo(),
                true),
            new SportRange(6,6)
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(14, $planning->createFirstBatch()->getLeaf()->getNumber());
    }

    public function testValidResourcesPerReferee(): void
    {
        $refereeInfo = new RefereeInfo(3);
        $planning = $this->createPlanning(
            $this->createInput([5], null, $refereeInfo)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $batch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $batch);
        $this->replaceReferee($batch, $planning->getInput()->getReferee(1), $planning->getInput()->getReferee(2), 2);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::UNEQUALLY_ASSIGNED_REFEREES,
            $validity & Validity::UNEQUALLY_ASSIGNED_REFEREES
        );
    }

    protected function replaceReferee(
        Batch $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amount = 1
    ): void {
        $amountReplaced = 0;
        /** @var AgainstGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $this->batchHasReferee($batch, $toReferee)) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->replaceReferee($nextBatch, $fromReferee, $toReferee, $amount);
        }
    }

    protected function batchHasReferee(Batch $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }

    public function testInvalidAssignedRefereePlaceSamePoule(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput(
                [3, 3],
                [$sportVariantWithFields],
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule))
            )
        );

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() !== SelfReferee::SamePoule,
            $firstBatch,
            $planning->getInput()->getPoule(1)->getPlace(1),
            $planning->getInput()->getPoule(2)->getPlace(1)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::INVALID_ASSIGNED_REFEREEPLACE,
            $validity & Validity::INVALID_ASSIGNED_REFEREEPLACE
        );
    }

    public function testValidResourcesPerRefereePlace(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput(
                [5],
                [$sportVariantWithFields],
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule))
            )
        );

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

        // ----------------- BEGIN EDITING --------------------------
//        (new PlanningOutput())->outputWithGames($planning, true);
        $pouleOne = $planning->getInput()->getPoule(1);
        $gamesPouleOne = $planning->getGamesForPoule($pouleOne);
        $refereePlaceTooMuch = $gamesPouleOne[0]->getRefereePlace();
        self::assertNotNull($refereePlaceTooMuch);
        foreach (array_reverse($gamesPouleOne) as $game) {
            if (!$game->isParticipating($refereePlaceTooMuch) && $game->getRefereePlace() !== $refereePlaceTooMuch) {
                $game->setRefereePlace($refereePlaceTooMuch);
                break;
            }
        }
//        (new PlanningOutput())->outputWithGames($planning, true);
        // ----------------- END EDITING --------------------------

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::UNEQUALLY_ASSIGNED_REFEREEPLACES,
            $validity & Validity::UNEQUALLY_ASSIGNED_REFEREEPLACES
        );
    }

    public function testValidResourcesPerRefereePlaceDifferentPouleSizes(): void
    {
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput(
                [5, 4],
                [$sportVariantWithFields],
                $refereeInfo
            )
        );
        $refereePlaceService = new RefereePlaceService($planning);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService->assign($firstBatch);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);
    }

    public function testValidityDescriptions(): void
    {
        $refereeInfo = new RefereeInfo(3);
        $planning = $this->createPlanning(
            $this->createInput([5, 4], null, $refereeInfo)
        );

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(Validity::ALL_INVALID, $planning);
        self::assertCount(17, $descriptions);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof Batch);
        $this->replaceReferee($firstBatch, $planning->getInput()->getReferee(3), $planning->getInput()->getReferee(1));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(Validity::ALL_INVALID, $planning);
        self::assertCount(17, $descriptions);
    }

    public function testNrOfHomeAwayH2H2(): void
    {
        $refereeInfo = new RefereeInfo();
        $sportVariant = new SportVariantWithFields($this->getAgainstH2hSportVariant(1, 1, 2), 2);
        $input = $this->createInput([3], [$sportVariant], $refereeInfo);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        // (new PlanningOutput())->outputWithGames($planning, true);

        // ---------------- MAKE INVALID --------------------- //
        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::Home)->first();
        $firstAwayGamePlace = $planningGame->getSidePlaces(AgainstSide::Away)->first();
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        self::assertInstanceOf(AgainstGamePlace::class, $firstAwayGamePlace);
        $planningGame->getPlaces()->removeElement($firstHomeGamePlace);
        $planningGame->getPlaces()->removeElement($firstAwayGamePlace);
        new AgainstGamePlace($planningGame, $firstAwayGamePlace->getPlace(), AgainstSide::Home);
        new AgainstGamePlace($planningGame, $firstHomeGamePlace->getPlace(), AgainstSide::Away);
        // ---------------- MAKE INVALID --------------------- //

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            Validity::UNEQUAL_PLACE_NROFHOMESIDES,
            $validity & Validity::UNEQUAL_PLACE_NROFHOMESIDES
        );
    }

    public function test6Places2FieldsMax2GamesInARow(): void
    {
        $refereeInfo = new RefereeInfo();
        $sportVariant = new SportVariantWithFields($this->getAgainstH2hSportVariant(), 2);
        $input = $this->createInput([6], [$sportVariant], $refereeInfo);
        $planning = new Planning($input, new SportRange(2, 2), 2);

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        $biggestPoule = $input->getPoule(1);
        $maxGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->createLogger());
//        $gameAssigner->disableThrowOnTimeout();
        $gameAssigner->assignGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(PlanningState::Succeeded, $planning->getState());

        $planningValidator = new PlanningValidator();

        $validity = $planningValidator->validate($planning);
        self::assertSame(Validity::VALID, $validity);
    }

    public function testValidate1Gpp1VS1(): void
    {
        $sportVariantWithFields = $this->getAgainstGppSportVariantWithFields(2, 1, 1, 1);
        $planning = $this->createPlanning($this->createInput([5], [$sportVariantWithFields]));
//        (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(2, $planning->getAgainstGames());
    }

    public function testValidate2Gpp1VS1(): void
    {
        $sportVariantWithFields = $this->getAgainstGppSportVariantWithFields(2, 1, 1, 2);
        $planning = $this->createPlanning($this->createInput([5], [$sportVariantWithFields]));
//        (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(5, $planning->getAgainstGames());
    }

}
