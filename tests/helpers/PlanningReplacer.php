<?php

namespace SportsScheduler\TestHelper;

use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Field as PlanningField;
use SportsPlanning\Referee as PlanningReferee;
use SportsScheduler\Resource\RefereePlaces\RefereePlaceReplacer as RefereePlaceReplacer;

trait PlanningReplacer
{
    protected function replaceRefereePlace(
        bool $samePoule,
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch,
        PlanningPlace $replaced,
        PlanningPlace $replacement
    ): void {
        (new RefereePlaceReplacer($samePoule))->replace($firstBatch, $replaced, $replacement);
    }

    protected function replaceField(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        PlanningField $replacedField,
        PlanningField $replacedByField,
        int $amount = 1
    ): bool {
        $nextBatch = $batch->getNext();
        if ($nextBatch === null) {
            return false;
        }
        return $this->replaceFieldHelper($nextBatch, $replacedField, $replacedByField, 0, $amount);
    }

    private function replaceFieldHelper(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        PlanningField $fromField,
        PlanningField $toField,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToField = $this->hasBatchField($batch, $toField);
        foreach ($batch->getGames() as $game) {
            if ($game->getField() !== $fromField || $batchHasToField) {
                continue;
            }
            $game->setField($toField);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replaceFieldHelper($nextBatch, $fromField, $toField, $amountReplaced, $maxAmount);
        }
        return false;
    }

    protected function hasBatchField(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch, PlanningField $field): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getField() === $field) {
                return true;
            }
        }
        return false;
    }

    protected function replaceReferee(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        PlanningReferee $replacedReferee,
        PlanningReferee $replacedByReferee,
        int $amount = 1
    ): bool {
        $nextBatch = $batch->getNext();
        if ($nextBatch === null) {
            return false;
        }
        return $this->replaceRefereeHelper($nextBatch, $replacedReferee, $replacedByReferee, 0, $amount);
    }

    private function replaceRefereeHelper(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToReferee = $this->hasBatchReferee($batch, $toReferee);
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereeNr() !== $fromReferee->refereeNr || $batchHasToReferee) {
                continue;
            }
            $game->setRefereeNr($toReferee->refereeNr);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replaceRefereeHelper(
                $nextBatch,
                $fromReferee,
                $toReferee,
                $amountReplaced,
                $maxAmount
            );
        }
        return false;
    }

    protected function hasBatchReferee(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getRefereeNr() === $referee->refereeNr) {
                return true;
            }
        }
        return false;
    }
}
