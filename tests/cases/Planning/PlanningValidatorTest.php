<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Planning;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Planning\PlanningValidity;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\Planning\PlanningState;
use SportsScheduler\Planning\PlanningValidator;
use SportsPlanning\Referee as PlanningReferee;
use SportsScheduler\Resource\RefereePlace\Service as RefereePlaceService;
use SportsScheduler\TestHelper\PlanningCreator;
use SportsScheduler\TestHelper\PlanningReplacer;

final class PlanningValidatorTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

//    public function testHasEnoughTotalNrOfGames(): void
//    {
//        $config = $this->createConfiguration([3,3]);
//        $planning = new Planning(new Input($config), new SportRange(1, 1), 1);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidity::NO_GAMES, $validity & PlanningValidity::NO_GAMES);
//    }

//    public function testHasEmptyGamePlace(): void
//    {
//        $sportsWithNrOfFieldsAndNrOfCycles = [
//            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1)
//        ];
//        $configuration = $this->createConfiguration([5], $sportsWithNrOfFieldsAndNrOfCycles);
//        $planning = $this->createPlanning($configuration);
//        $firstGame = $planning->getGames()[0];
//        self::assertNotFalse($firstGame);
//        $firstGame->getPlaces()->clear();
//
//        //(new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidity::EMPTY_PLACE, $validity & PlanningValidity::EMPTY_PLACE);
//    }

    public function testHasEmptyGameRefereePlace(): void
    {
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidity::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // --------- BEGIN EDITING --------------
        /** @var AgainstGame $firstGame */
        $firstGame = $planning->getGames()[0];
        $firstGame->setRefereePlaceUniqueIndex(null);
//        $firstBatch = $planning->createFirstBatch();
//        $firstBatch->removeAsReferee( $firstGame->getRefereePlace()/*, $firstGame*/ );
        // --------- BEGIN EDITING --------------
        //(new PlanningOutput())->outputWithGames($planning, true);

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::EMPTY_REFEREEPLACE,
            $validity & PlanningValidity::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee(): void
    {
        $refereeInfo = RefereeInfo::fromNrOfReferees(2);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getGames()[0];
        $planningGame->setRefereeNr(null);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidity::EMPTY_REFEREE, $validity & PlanningValidity::EMPTY_REFEREE);
    }

//    public function testAllPlacesSameNrOfGames(): void
//    {
//        $refereeInfo = new PlanningRefereeInfo();
//        $nrOfPlaces = 5;
//
//        $planningConfig = $this->createConfiguration(
//            [$nrOfPlaces],
//            null,
//            $refereeInfo
//        );
//        $planning = $this->createPlanning($planningConfig, new SportRange(1, 1), 1);
//
//        $planningValidator = new PlanningValidator();
//
//        /** @var AgainstGame $planningGame */
//        $planningGame = $planning->getAgainstGames()->first();
//        $planning->getAgainstGames()->removeElement($planningGame);
//
//        (new PlanningOutput())->output($planning, PlanningOutput\Extra::Games->value + PlanningOutput\Extra::Input->value);
//
//        self::assertSame(PlanningValidity::UNEQUAL_GAME_AGAINST, $planningValidator->validate($planning));
//    }

//    public function testGamesInARow(): void
//    {
//        $configuration = $this->createConfiguration([4]);
//        $planning = $this->createPlanning($configuration, null);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidity::VALID, $validity);
//
//        // (new PlanningOutput())->output($planning, PlanningOutput\Extra::Games);
//
//        // ---------------- MAKE INVALID --------------------- //
//        $refObject   = new ReflectionObject($planning);
//        $refProperty = $refObject->getProperty('maxNrOfGamesInARow');
//        // $refProperty->setAccessible(true);
//        $refProperty->setValue($planning, 1);
//        // ---------------- MAKE INVALID --------------------- //
//
////        (new PlanningOutput())->outputWithGames($planning, true);
//
//
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(
//            PlanningValidity::TOO_MANY_GAMES_IN_A_ROW,
//            $validity & PlanningValidity::TOO_MANY_GAMES_IN_A_ROW
//        );
//    }

//    public function testGameUnequalHomeAway(): void
//    {
//        $configuration = $this->createConfiguration([2]);
//        $planning = $this->createPlanning($configuration);
//
//        $planningGame = $planning->getAgainstGames()->first();
//        self::assertInstanceOf(AgainstGame::class, $planningGame);
//        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::Home)->first();
//        // $firstHomePlace = $firstHomeGamePlace->getPlace();
//        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
//        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
//        $planningGame->getPlaces()->add($firstHomeGamePlace);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(
//            PlanningValidity::UNEQUAL_GAME_HOME_AWAY,
//            $validity & PlanningValidity::UNEQUAL_GAME_HOME_AWAY
//        );
//    }

    public function testBatchMultipleFields(): void
    {
        $refereeInfo = RefereeInfo::fromNrOfReferees(2);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(2, 2));

        $planningGame = $planning->getGames()[0];
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->fieldNr === 1 ? 2 : 1;
        $planningGame->setField($planning->getSport(1)->getField($newFieldNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH,
            PlanningValidity::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH & $validity
        );
    }


    public function testBatchMultipleReferees(): void
    {
        $refereeInfo = RefereeInfo::fromNrOfReferees(2);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration, new SportRange(2, 2));

        $planningGame = $planning->getGames()[0];
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $refereeNr = $planningGame->getRefereeNr();
        self::assertNotNull($refereeNr);
        $newRefereeNr = $refereeNr === 1 ? 2 : 1;
        $planningGame->setRefereeNr($newRefereeNr);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH,
            PlanningValidity::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH & $validity
        );
    }

    public function testValidResourcesPerBatch(): void
    {
        $refereeInfo = RefereeInfo::fromNrOfReferees(2);

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidity::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 3, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false);
        $planning = $this->createPlanning($configuration);

        $planningGame = $planning->getGames()[0];
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->fieldNr === 3 ? 1 : 3;
        $planningGame->setField($planning->getSport(1)->getField($newFieldNr));

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::UNEQUALLY_ASSIGNED_FIELDS,
            $validity & PlanningValidity::UNEQUALLY_ASSIGNED_FIELDS
        );
    }
    public function testValidatePerPouleTooMuchCompetitors(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([8,8,8]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            true);
        $planning = $this->createPlanning($configuration, new SportRange(6,6));

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(14, $planning->createFirstBatch()->getLeaf()->getNumber());
    }

    public function testValidResourcesPerReferee(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromNrOfReferees(3);
        $configuration = new PlanningConfiguration(
            new PouleStructure([8,8,8]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $batch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $batch);
        $this->replaceReferee($batch, $planning->getReferee(1), $planning->getReferee(2), 2);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::UNEQUALLY_ASSIGNED_REFEREES,
            $validity & PlanningValidity::UNEQUALLY_ASSIGNED_REFEREES
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
            if ($game->getRefereeNr() !== $fromReferee->refereeNr || $this->batchHasReferee($batch, $toReferee->refereeNr)) {
                continue;
            }
            $game->setRefereeNr($toReferee->refereeNr);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->replaceReferee($nextBatch, $fromReferee, $toReferee, $amount);
        }
    }

    protected function batchHasReferee(Batch $batch, int $refereeNr): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereeNr() === $refereeNr) {
                return true;
            }
        }
        return false;
    }

    public function testInvalidAssignedRefereePlaceSamePoule(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([3,3]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoules);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoules);
        $this->replaceRefereePlace(
            $refereeInfo->selfRefereeInfo?->selfReferee !== SelfReferee::SamePoule,
            $firstBatch,
            $planning->getPoule(1)->getPlace(1),
            $planning->getPoule(2)->getPlace(1)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE,
            $validity & PlanningValidity::INVALID_ASSIGNED_REFEREEPLACE
        );
    }

    public function testValidResourcesPerRefereePlace(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoules
        );
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

        // ----------------- BEGIN EDITING --------------------------
//        (new PlanningOutput())->outputWithGames($planning, true);
        $pouleOne = $planning->getPoule(1);
        $gamesPouleOne = $pouleOne->getGames();
        $refereePlaceUniqueIndexTooMuch = $gamesPouleOne[0]->getRefereePlaceUniqueIndex();
        self::assertNotNull($refereePlaceUniqueIndexTooMuch);

        foreach (array_reverse($gamesPouleOne) as $game) {
            if ( $game->getRefereePlaceUniqueIndex() !== $refereePlaceUniqueIndexTooMuch) {
                $startPlaceNrPos = strpos($refereePlaceUniqueIndexTooMuch, ".", );
                if( $startPlaceNrPos === false ) {
                    throw new Exception('UniqueIndex should contain ' . $refereePlaceUniqueIndexTooMuch);
                }
                $placeNr = (int)substr($refereePlaceUniqueIndexTooMuch, $startPlaceNrPos + 1);
                $isParticipating = $game->isParticipating($placeNr);
                if( $firstBatch instanceof SelfRefereeBatchOtherPoules || !$isParticipating ) {
                    $game->setRefereePlaceUniqueIndex($refereePlaceUniqueIndexTooMuch);
                    break;
                }
            }
        }
//        (new PlanningOutput())->outputWithGames($planning, true);
        // ----------------- END EDITING --------------------------

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidity::UNEQUALLY_ASSIGNED_REFEREEPLACES,
            $validity & PlanningValidity::UNEQUALLY_ASSIGNED_REFEREEPLACES
        );
    }

    public function testValidResourcesPerRefereePlaceDifferentPouleSizes(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $configuration = new PlanningConfiguration(
            new PouleStructure([5, 4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        $refereePlaceService = new RefereePlaceService($planning);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoules);
        $refereePlaceService->assign($firstBatch);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidity::VALID, $validity);
    }

    public function testValidityDescriptions(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromNrOfReferees(3);
        $configuration = new PlanningConfiguration(
            new PouleStructure([5, 4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            false);
        $planning = $this->createPlanning($configuration);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidity::ALL_INVALID, $planning);
        self::assertCount(17, $descriptions);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof Batch);
        $this->replaceReferee($firstBatch, $planning->getReferee(3), $planning->getReferee(1));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidity::ALL_INVALID, $planning);
        self::assertCount(17, $descriptions);
    }

//    public function testNrOfHomeAwayH2H2(): void
//    {
//        $refereeInfo = new PlanningRefereeInfo();
//        $sportsWithNrOfFieldsAndNrOfCycles = [
//            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 2),
//        ];
//        $configuration = $this->createConfiguration(
//            [3],
//            $sportsWithNrOfFieldsAndNrOfCycles,
//            $refereeInfo);
//        $planning = $this->createPlanning($configuration);
//
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        // ---------------- MAKE INVALID --------------------- //
//        $planningGame = $planning->getAgainstGames()->first();
//        self::assertInstanceOf(AgainstGame::class, $planningGame);
//        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::Home)->first();
//        $firstAwayGamePlace = $planningGame->getSidePlaces(AgainstSide::Away)->first();
//        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
//        self::assertInstanceOf(AgainstGamePlace::class, $firstAwayGamePlace);
//        $planningGame->getPlaces()->removeElement($firstHomeGamePlace);
//        $planningGame->getPlaces()->removeElement($firstAwayGamePlace);
//        new AgainstGamePlace($planningGame, $firstAwayGamePlace->getPlace(), AgainstSide::Home);
//        new AgainstGamePlace($planningGame, $firstHomeGamePlace->getPlace(), AgainstSide::Away);
//        // ---------------- MAKE INVALID --------------------- //
//
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(
//            PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES,
//            $validity & PlanningValidity::UNEQUAL_PLACE_NROFHOMESIDES
//        );
//    }

    public function test6Places2FieldsMax2GamesInARow(): void
    {

        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([6]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planning = $this->createPlanning($configuration, new SportRange(2, 2), 2);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(PlanningState::Succeeded, $planning->getState());

        $planningValidator = new PlanningValidator();

        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidity::VALID, $validity);
    }
}
