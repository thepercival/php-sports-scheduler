<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\GameCreatorFromSchedule as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\PlanningRefereeInfo;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;

trait PlanningCreator
{
    protected function getAgainstH2hSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2h = 1
    ): AgainstH2h {
        return new AgainstH2h($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2h);
    }

    protected function getAgainstGppSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): AgainstGpp {
        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
    }

    protected function getSingleSportVariant(int $nrOfGamesPerPlace = 1, int $nrOfGamePlaces = 1): SingleSportVariant
    {
        return new SingleSportVariant($nrOfGamePlaces, $nrOfGamesPerPlace);
    }

    protected function getAllInOneGameSportVariant(int $nrOfGamesPerPlace = 1): AllInOneGameSportVariant
    {
        return new AllInOneGameSportVariant($nrOfGamesPerPlace);
    }

    protected function getAgainstH2hSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2h = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstH2hSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2h),
            $nrOfFields
        );
    }

    protected function getAgainstGppSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstGppSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace),
            $nrOfFields
        );
    }

    protected function getSingleSportVariantWithFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1,
        int $nrOfGamePlaces = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getSingleSportVariant($nrOfGamesPerPlace, $nrOfGamePlaces),
            $nrOfFields
        );
    }

    protected function getAllInOneGameSportVariantWithFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields($this->getAllInOneGameSportVariant($nrOfGamesPerPlace), $nrOfFields);
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $processor = new UidProcessor();
//        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LogLevel::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getDefaultNrOfReferees(): int
    {
        return 2;
    }

    /**
     * @param list<int> $pouleStructureAsArray
     * @param list<SportVariantWithFields>|null $sportVariantsWithFields
     * @param PlanningRefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array|null $sportVariantsWithFields = null,
        PlanningRefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new PlanningRefereeInfo($this->getDefaultNrOfReferees());
        }
        $input = new Input( new Input\Configuration(
            new PouleStructure(...$pouleStructureAsArray),
            $sportVariantsWithFields,
            $refereeInfo,
            $perPoule
        ) );

        return $input;
    }

    protected function createPlanning(
        Input $input,
        SportRange|null $nrOfGamesPerBatchRange = null,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false,
        TimeoutState|null $timeoutState = null,
        int|null $allowedGppMargin = null
    ): Planning {
        if ($nrOfGamesPerBatchRange === null) {
            $nrOfGamesPerBatchRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $nrOfGamesPerBatchRange, $maxNrOfGamesInARow);
        if ($timeoutState !== null) {
            $planning->setTimeoutState($timeoutState);
        }

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($input->createSportVariants());
        if( $allowedGppMargin === null ) {
            $biggestPoule = $input->getPoule(1);
            $allowedGppMargin = $scheduleCreator->getMaxGppMargin($sportVariantsWithNr, count($biggestPoule->getPlaces()));
        }
        $pouleStructure = $input->createPouleStructure();
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $allowedGppMargin);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        if ($disableThrowOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        if ($showHighestCompletedBatchNr) {
            $gameAssigner->showHighestCompletedBatchNr();
        }
        if( $gameAssigner->assignGames($planning) !== PlanningState::Succeeded ) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
