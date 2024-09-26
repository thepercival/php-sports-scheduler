<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;

use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsHelpers\SportVariants\AgainstGpp;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Schedule\Creator as ScheduleCreator;

trait PlanningCreator
{
    use LoggerCreator;
    use GppMarginCalculator;

    protected function getAgainstH2hSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): AgainstH2h {
        return new AgainstH2h($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H);
    }

    protected function getAgainstGppSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): AgainstGpp {
        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
    }

    protected function getSingleSportVariant(int $nrOfGamesPerPlace = 1, int $nrOfGamePlaces = 1): Single
    {
        return new Single($nrOfGamePlaces, $nrOfGamesPerPlace);
    }

    protected function getAllInOneGameSportVariant(int $nrOfGamesPerPlace = 1): AllInOneGame
    {
        return new AllInOneGame($nrOfGamesPerPlace);
    }

    protected function getAgainstH2hSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstH2hSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H),
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

    protected function getDefaultNrOfReferees(): int
    {
        return 2;
    }

    /**
     * @param list<int> $pouleStructureAsArray
     * @param list<SportVariantWithFields>|null $sportVariantsWithFields
     * @param RefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array|null $sportVariantsWithFields = null,
        RefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
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
        SportRange $nrOfGamesPerBatchRange = null,
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

        $scheduleCreator = new ScheduleCreator($this->createLogger());
        if( $allowedGppMargin === null ) {
            $biggestPoule = $input->getPoule(1);
            $allowedGppMargin = $this->calculateMaxGppMargin($biggestPoule);
        }
        $schedules = $scheduleCreator->createFromInput($input, $allowedGppMargin);

        $gameCreator = new GameCreator($this->createLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->createLogger());
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
