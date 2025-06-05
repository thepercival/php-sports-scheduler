<?php

namespace SportsScheduler\Resource;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Output\BatchOutput;
use SportsPlanning\Output\GameOutput;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\TimeoutConfig;
use SportsScheduler\Exceptions\TimeoutException;
use SportsScheduler\Resource\Fields as FieldResources;
use SportsScheduler\Resource\RefereePlace\Predicter;
use SportsScheduler\Resource\Service\ResourceServiceHelper;
use SportsScheduler\Resource\Service\PlanningCounters;
use SportsScheduler\Resource\Service\RefereeService;

final class ResourceService
{
    private DateTimeImmutable|null $timeoutDateTime = null;
    private Predicter $refereePlacePredicter;
    protected BatchOutput $batchOutput;
    protected PlanningOutput $planningOutput;
    protected GameOutput $gameOutput;
    protected bool $throwOnTimeout;
    protected bool $showHighestCompletedBatchNr = false;
    protected bool $sortWhenReachedHighestCompletedBatchNr = false;
    protected int $highestCompletedBatchNr = 0;
    protected TimeoutConfig $timeoutConfig;
    protected int $debugCounter = 0;
//    /**
//     * @var array<int, AgainstH2h|AgainstGpp|Single>
//     */
//    protected array $sportVariantMap;
    protected ResourceServiceHelper $helper;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->helper = new ResourceServiceHelper($planning, $logger);
        $poules = $planning->poules;
        $this->refereePlacePredicter = new Predicter($poules);
        $this->batchOutput = new BatchOutput($logger);
        $this->planningOutput = new PlanningOutput($logger);
        $this->gameOutput = new GameOutput($logger);
//        $this->initSportVariantMap($planning->getInput());
        $this->throwOnTimeout = true;
        $this->timeoutConfig = new TimeoutConfig();
        $nextTimeoutState = $this->timeoutConfig->nextTimeoutState($planning);
        $this->sortWhenReachedHighestCompletedBatchNr = $this->timeoutConfig->useSort($nextTimeoutState);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return PlanningState
     */
    public function assign(array $games, int $maxNrOfBatches): PlanningState
    {
        $oCurrentDateTime = new DateTimeImmutable();
        $configuration = $this->planning->getConfiguration();
        $nextTimeoutState = $this->timeoutConfig->nextTimeoutState($this->planning);
        $timeoutSeconds = $this->timeoutConfig->getTimeoutSeconds($configuration, $nextTimeoutState);
        $this->timeoutDateTime = $oCurrentDateTime->add(new \DateInterval('PT' . $timeoutSeconds . 'S'));
        $batch = new Batch();
        $selfReferee = $configuration->refereeInfo->selfRefereeInfo->selfReferee;

        if ($selfReferee === SelfReferee::SamePoule) {
            $batch = new SelfRefereeBatchSamePoule($batch);
        } else if ($selfReferee === SelfReferee::OtherPoules) {
            $batch = new SelfRefereeBatchOtherPoules($this->planning->poules, $batch);
        }


        try {
            $fieldResources = new FieldResources($this->planning);
            $assignedBatch = $this->assignBatch($games, $maxNrOfBatches, $fieldResources, $batch);
            if ($assignedBatch === null) {
                return PlanningState::Failed;
            }
            if ($assignedBatch instanceof Batch) {
                $refereeService = new RefereeService($this->planning->referees);
                $refereeService->assign($assignedBatch->getFirst());
            }

            // $this->batchOutput->output($batch->getFirst(), ' final : iterations = ' . $this->debugIterations );
        } catch (TimeoutException $e) {
            return PlanningState::TimedOut;
        }
        return PlanningState::Succeeded;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param int $maxNrOfBatches
     * @param FieldResources $fieldResources
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
     * @return Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null
     * @throws TimeoutException
     */
    protected function assignBatch(
        array $games,
        int $maxNrOfBatches,
        FieldResources $fieldResources,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
    ): Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null {
        $this->highestCompletedBatchNr = 0;
        if ($this->assignBatchHelper(
            $games,
            $games,
            $fieldResources,
            $batch,
            [],
            $this->planning->maxNrOfBatchGames,
            $maxNrOfBatches
        )) {
            return $this->getActiveLeaf($batch->getLeaf());
        }
        return null;
    }

    protected function getActiveLeaf(Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch): Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules
    {
        $previousBatch = $batch->getPrevious();
        if ($previousBatch === null) {
            return $batch;
        }
        if (count($previousBatch->getGames()) === $this->planning->maxNrOfBatchGames) {
            return $batch;
        }
        return $this->getActiveLeaf($previousBatch);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param list<TogetherGame|AgainstGame> $gamesForBatch
     * @param FieldResources $fieldResources
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
     * @param list<Place> $requiredPlacesForBatch
     * @param int $maxNrOfBatchGames
     * @param int $maxNrOfBatches
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        FieldResources $fieldResources,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        array $requiredPlacesForBatch,
        int $maxNrOfBatchGames,
        int $maxNrOfBatches,
        int $nrOfGamesTried = 0
    ): bool {

//        if ($batch->getNumber() === 32) {
//            $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber(), new SportRange(32, 32));
//            $this->logger->info('unassinged games: ');
//            $this->batchOutput->outputGames($gamesForBatch);
//            $c = 12;
//        }

        // huidige in batch : $batch->getGames()
        // nog te verwerken is : $batch->getGames() - $games

        // wanneer nog te verwerken > 0 doorgaan
        // anders als huidige

        // als batch vol
        // of het aantal te verwerken is minder dan $maxNrOfBatchGames

        $allGamesAssigned = count($batch->getGames()) === count($games);
        if( $allGamesAssigned ) {
            return true;
        }

        $toNextBatch = count($batch->getGames()) === $maxNrOfBatchGames;
        if ($toNextBatch) {
            if (!$this->refereePlacesCanBeAssigned($batch)) {
                return false;
            }

//            $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber() );
//            $this->logger->info('unassinged games: ');
//            $this->batchOutput->outputGames($games);
//            if( $nextBatch->getNumber() === 10 ) {
//                $er = 4;
//            }

           $nextBatch = $this->toNextBatch($batch, $fieldResources, $games);

            // $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber(), 1, 1);
//            if ($nextBatch->getNumber() === 12) {
//                $er = 12;
//            }

            $doSort = false;
            if ($batch->getNumber() > $this->highestCompletedBatchNr) {
                $this->highestCompletedBatchNr = $batch->getNumber();
                $doSort = $this->sortWhenReachedHighestCompletedBatchNr;
                if ($this->showHighestCompletedBatchNr) {
                    $this->logger->info('batch ' . $batch->getNumber() . ' completed');
                }
            }

            // ------------- BEGIN: OUTPUT --------------- //
//            if ($batch->getNumber() === 87) {
//            //                ++$this->debugCounter;
//            //                if( $this->debugCounter === 122) {
//                // $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber(), 1, 1);
//                $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber());
//                $this->logger->info('unassinged games: ');
//                $this->batchOutput->outputGames($games);
//            //                $this->logger->info('unassinged games: ' . ++$this->debugCounter);
//                $c = 12;
//            //                }
//            }
//             ------------- END: OUTPUT --------------- //

            $unassignedPlanningCounters = new PlanningCounters($this->planning->sports, $games);
            if (!$this->helper->canGamesBeAssigned($batch->getNumber(), $maxNrOfBatches, $unassignedPlanningCounters)) {
//                $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber());
//                $this->logger->info(' batch completed nr ' . $batch->getNumber());
//                $this->logger->info('unassinged games: ');
//                $this->batchOutput->outputGames($games);
//                if (count($games) >= $this->planning->getMinNrOfBatchGames()
//                    && !$this->helper->canGamesCanBeAssigned($batch->getNumber(), new InfoToAssign($games))) {
//                    return false;
//                }
                return false;
            }
//            if ($batch->getNumber() >= 8) {
////                            $this->logger->info(
////                                ' nr of games to process before gamesinarow-filter(max ' . $this->planning->getMaxNrOfGamesInARow(
////                                ) . ') : ' . count($games)
////                            );
//            //                $this->gameOutput->outputGames($games);
////                $this->logger->info('unassinged games: ');
////                $this->batchOutput->outputGames($games);
//
//                $unassignedPlanningCounters = new PlanningCounters($games);
//                if (!$this->helper->canGamesBeAssigned($batch->getNumber(), $unassignedPlanningCounters)) {
//                   return false;
//                }
//            }
            $gamesForBatchTmp = array_filter(
                $games,
                function (TogetherGame|AgainstGame $game) use ($nextBatch): bool {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );

//            $maxNrOfBatchesTmp = $this->planning->getMaxNrOfBatches() - $batch->getNumber();
//            $this->logger->info('batch '.$batch->getNumber().' completed, trying for batch '.$nextBatch->getNumber().', ' . $maxNrOfBatchesTmp . ' to go');

            if ($doSort) {
//                if ($batch->getNumber() === 5) {
//                    $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber());
//                    $this->logger->info('unassigned pre sorted games: ');
//                    $this->batchOutput->outputGames($gamesForBatchTmp);
//                }
                $this->helper->sortGamesForNextBatch($batch, $gamesForBatchTmp, $unassignedPlanningCounters);
//                if ($batch->getNumber() === 5) {
//                    $this->logger->info('unassigned post sorted games: ');
//                    $this->batchOutput->outputGames($gamesForBatchTmp);
//                    $er = 12;
//                }
            }

//            $this->logger->info(' nr of games to process after gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($gamesForBatchTmp) );
//            $this->gameOutput->outputGames($gamesForBatchTmp);
            $gamesList = array_values($gamesForBatchTmp);

            $requiredPlacesForNextBatch = $this->helper->getRequiredPlaces($batch->getNumber(), $unassignedPlanningCounters);

//            if ($batch->getNumber() === 8) {
//                $this->logger->info('required place for next batch: ');
//                $this->logger->info( join(', ', array_map(function(Place $place): string {
//                    return (new PlaceOutput($this->logger))->getPlace($place, null, true);
//                }, $requiredPlacesForNextBatch)));
//                $er = 12;
//            }

            $maxNrOfBatchGames = $this->planning->maxNrOfBatchGames;
            return $this->assignBatchHelper(
                $games,
                $gamesList,
                $fieldResources,
                $nextBatch,
                $requiredPlacesForNextBatch,
                $maxNrOfBatchGames,
                $maxNrOfBatches,
            );
        }
        if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            $nextTimeoutState = $this->timeoutConfig->nextTimeoutState($this->planning);
            $configuration = $this->planning->getConfiguration();
            $timeoutSeconds = $this->timeoutConfig->getTimeoutSeconds($configuration, $nextTimeoutState);
            throw new TimeoutException('exceeded maximum duration of ' . $timeoutSeconds . ' seconds', E_ERROR);
        }
        $minNrOfBatchGames = $this->planning->minNrOfBatchGames;
        if (count($games) >= $minNrOfBatchGames
            && (count($gamesForBatch) + count($batch->getGames())) < $minNrOfBatchGames) {
            return false;
        }

        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
        $game = array_shift($gamesForBatch);
        if ($game === null) {
            return false;
        }
//            $this->logger->info('batch: '.$batch->getNumber().', nrOfGamesTried: '.$nrOfGamesTried);

        if ($this->isGameAssignable($batch, $game, $fieldResources)) {
            $newFieldResources = clone $fieldResources; // ->copy($this->planning);
            $this->assignGame($batch, $game, $newFieldResources, $requiredPlacesForBatch);

            if ($this->areAllRequiredPlacesAssignable($batch, $requiredPlacesForBatch)) {
                $gamesForBatchTmp = array_values(
                    array_filter(
                        $gamesForBatch,
                        function (TogetherGame|AgainstGame $game) use ($batch): bool {
                            return $this->areAllPlacesAssignable($batch, $game);
                        }
                    )
                );
                if ($this->assignBatchHelper(
                    $games,
                    $gamesForBatchTmp,
                    $newFieldResources,
                    $batch,
                    $requiredPlacesForBatch,
                    $maxNrOfBatchGames,
                    $maxNrOfBatches
                )) {
                    return true;
                }
            } // else {
//                    $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber());
//                    $this->logger->info('unassinged games: ');
//                    $this->batchOutput->outputGames($games);
//                    $er = 12;
            // }

            $this->releaseGame($batch, $game);
        }
        $gamesForBatch[] = $game;
        ++$nrOfGamesTried;
        if ($this->assignBatchHelper(
            $games,
            $gamesForBatch,
            clone $fieldResources,
            $batch,
            $requiredPlacesForBatch,
            $maxNrOfBatchGames,
            $maxNrOfBatches,
            $nrOfGamesTried
        )) {
            return true;
        }
        if ($this->planning->isNrOfBatchGamesUnequal() && $maxNrOfBatchGames > $this->planning->minNrOfBatchGames) {
            $gamesForBatch[] = $game;
            if ($this->assignBatchHelper(
                $games,
                $gamesForBatch,
                clone $fieldResources,
                $batch,
                $requiredPlacesForBatch,
                $maxNrOfBatchGames - 1,
                $maxNrOfBatches
            )) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
     * @param TogetherGame|AgainstGame $game
     * @param Fields $fieldResources
     * @param list<Place> $requiredPlaces
     * @throws \Exception
     */
    protected function assignGame(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        TogetherGame|AgainstGame $game,
        FieldResources $fieldResources,
        array &$requiredPlaces,
    ): void {
        $fieldResources->assignToGame($game);
        $batch->add($game);
        $game->setBatchNr($batch->getNumber());
        foreach ($game->poule->places as $place) {
            $idx = array_search($place, $requiredPlaces, true);
            if ($idx !== false) {
                array_splice($requiredPlaces, $idx, 1);
            }
        }
    }

    protected function releaseGame(Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch, TogetherGame|AgainstGame $game): void
    {
        $batch->remove($game);
    }

    /**
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
     * @param FieldResources $fieldResources
     * @param list<TogetherGame|AgainstGame> $games
     * @return Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules
     */
    protected function toNextBatch(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        FieldResources $fieldResources,
        array &$games
    ): Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules {
        $fieldResources->fill();
        foreach ($batch->getGames() as $game) {
            $foundGameIndex = array_search($game, $games, true);
            if ($foundGameIndex !== false) {
                array_splice($games, $foundGameIndex, 1);
            }
        }
        return $batch->createNext();
    }

    private function isGameAssignable(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        TogetherGame|AgainstGame $game,
        Fields $fieldResources
    ): bool {
        if (!$fieldResources->isSomeFieldAssignable($game->getField()->sportNr, $game->poule)) {
            return false;
        }
        if (!$this->areAllPlacesAssignable($batch, $game)) {
            return false;
        }
        if (!($batch instanceof SelfRefereeBatchSamePoule)) {
            return true;
        }
        return $this->hasPouleRefereePlaceAvailable($batch, $game);
    }

    // de wedstrijd is assignbaar als
    // 1 alle plekken, van een wedstrijd, nog niet in de batch
    // 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
    private function areAllPlacesAssignable(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        $maxNrOfGamesInARow = $this->planning->maxNrOfGamesInARow;
        foreach ($game->getPlaces() as $place) {
            if ($batch->isParticipating($place)) {
                return false;
            }
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place);
            if ($nrOfGamesInARow < $maxNrOfGamesInARow || $maxNrOfGamesInARow <= 0) {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
     * alle verplichte plaatsen voor batch
     *
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch
     * @param list<Place> $requiredPlaces
     * @return bool
     */
    private function areAllRequiredPlacesAssignable(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        array $requiredPlaces
    ): bool {
        $nrOfUnassignedPlaces = count($batch->getUnassignedPlaces());
        return $nrOfUnassignedPlaces >= count($requiredPlaces);
    }

    private function areAllPlacesAssignableByGamesInARow(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        if ($this->planning->maxNrOfGamesInARow === 0) {
            return true;
        }
        foreach ($game->getPlaces() as $place) {
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place) + 1;
            if ($nrOfGamesInARow > $this->planning->maxNrOfGamesInARow) {
                return false;
            }
        }
        return true;
    }

    protected function hasPouleRefereePlaceAvailable(
        SelfRefereeBatchSamePoule $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        $poule = $game->poule;
        $nrOfRefereePlacesPerGame = 1;
        $nrOfPlacesAlreadyParticipatingInBatch = $batch->getNrOfPlacesParticipating($poule, $nrOfRefereePlacesPerGame);
        $nrAvailable = count($poule->places) - $nrOfPlacesAlreadyParticipatingInBatch;
        return $nrAvailable >= (count($game->getPlaces()) + $nrOfRefereePlacesPerGame);
    }

    protected function refereePlacesCanBeAssigned(
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules $batch): bool
    {
        // naast forced refereeplaces and teveel

        if ($batch instanceof SelfRefereeBatchSamePoule
            || $batch instanceof SelfRefereeBatchOtherPoules) {
            $selfReferee = $this->planning->getConfiguration()->refereeInfo->selfRefereeInfo->selfReferee;
            return $this->refereePlacePredicter->canStillAssign($batch, $selfReferee);
        }
        return true;
    }

//    private function initSportVariantMap(Input $input): void
//    {
//        $this->sportVariantMap = [];
//        foreach ($input->getSports() as $sport) {
//            $variant = $sport->createVariant();
//            if ($variant instanceof AllInOneGame) {
//                continue;
//            }
//            $this->sportVariantMap[$sport->getNumber()] = $variant;
//        }
//    }
//
//    public function getSportVariant(Sport $sport): AgainstH2h|AgainstGpp|Single
//    {
//        return $this->sportVariantMap[$sport->getNumber()];
//    }

    public function showHighestCompletedBatchNr(): void
    {
        $this->showHighestCompletedBatchNr = true;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
