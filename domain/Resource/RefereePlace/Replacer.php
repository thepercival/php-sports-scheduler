<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\RefereePlace;

use DateTimeImmutable;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Resource\GameCounter\GameCounterForPlace;
use SportsScheduler\Exceptions\TimeoutException;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsScheduler\Planning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\Resource\GameCounter;
use SportsScheduler\Resource\GameCounter\Unequal as UnequalResource;

class Replacer
{
    protected DateTimeImmutable|null $timeoutDateTime = null;
    /**
     * @var list<Replace>
     */
    protected array $revertableReplaces;
    private bool $throwOnTimeout;

    public function __construct(protected bool $samePoule)
    {
        $this->revertableReplaces = [];
        $this->throwOnTimeout = true;
    }

    public function setTimeoutDateTime(DateTimeImmutable $timeoutDateTime): void
    {
        $this->timeoutDateTime = $timeoutDateTime;
    }

    /**
     * @param Planning $planning
     * @param SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch
     * @return bool
     */
    public function replaceUnequals(Planning $planning,
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch): bool
    {
        $gameAssignmentValidator = new GameAssignmentValidator($planning);
        $unequals = $gameAssignmentValidator->getRefereePlaceUnequals();
        if (count($unequals) === 0) {
            return true;
        }
        foreach ($unequals as $unequal) {
            if (!$this->replaceUnequal($firstBatch, $unequal)) {
                $this->revertReplaces();
                return false;
            }
        }
        return $this->replaceUnequals($planning, $firstBatch);
    }

    protected function replaceUnequal(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch, UnequalResource $unequal): bool
    {
        return $this->replaceUnequalHelper($firstBatch, $unequal->getMinGameCounters(), $unequal->getMaxGameCounters());
    }

    /**
     * @param SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch
     * @param array<int|string,GameCounter> $minGameCounters
     * @param array<int|string,GameCounter> $maxGameCounters
     * @return bool
     */
    protected function replaceUnequalHelper(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $firstBatch,
        array $minGameCounters,
        array $maxGameCounters): bool
    {
        if (count($minGameCounters) === 0 || count($maxGameCounters) === 0) {
            return true;
        }

        /** @var GameCounterForPlace $replacedGameCounter */
        foreach ($maxGameCounters as $replacedGameCounter) {
            /** @var GameCounterForPlace $replacementGameCounter */
            foreach ($minGameCounters as $replacementGameCounter) {
                if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
                    throw new TimeoutException(
                        "exceeded timeout while replacing selfreferee",
                        E_ERROR
                    );
                }
                if (!$this->replace(
                    $firstBatch,
                    $replacedGameCounter->place,
                    $replacementGameCounter->place,
                )) {
                    continue;
                }

                if (isset($maxGameCounters[$replacedGameCounter->getIndex()])) {
                    unset($maxGameCounters[$replacedGameCounter->getIndex()]);
                }
                if (isset($minGameCounters[$replacementGameCounter->getIndex()])) {
                    unset($minGameCounters[$replacementGameCounter->getIndex()]);
                }
                return $this->replaceUnequalHelper($firstBatch, $minGameCounters, $maxGameCounters);
            }
        }
        return false;
    }

    public function replace(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        Place $replaced,
        Place $replacement
    ): bool {
        $batchHasReplacement = $batch->getBase()->isParticipating($replacement)
            || $batch->isParticipatingAsReferee($replacement);
        if (!$batchHasReplacement && $batch->isParticipatingAsReferee($replaced)) {
            foreach ($batch->getBase()->getGames() as $game) {
                $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
                if ($refereePlaceUniqueIndex === null || $refereePlaceUniqueIndex !== $replaced->getUniqueIndex()) {
                    continue;
                }
                if (($game->poule->pouleNr === $replacement->pouleNr && !$this->samePoule)
                    || ($game->poule->pouleNr !== $replacement->pouleNr && $this->samePoule)) {
                    continue;
                }
                $replace = new Replace($batch, $game, $replacement->getUniqueIndex(), $refereePlaceUniqueIndex);
                if ($this->isAlreadyReplaced($replace)) {
                    return false;
                }
                $this->revertableReplaces[] = $replace;
                $game->setRefereePlaceUniqueIndex(null);
                $batch->removeReferee($refereePlaceUniqueIndex);
                $game->setRefereePlaceUniqueIndex($replacement->getUniqueIndex());
                $batch->addRefereeUniqueIndex($replacement->getUniqueIndex());
                return true;
            }
        }

        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replace($nextBatch, $replaced, $replacement);
        }
        return false;
    }

    protected function isAlreadyReplaced(Replace $replace): bool
    {
        foreach ($this->revertableReplaces as $revertableReplace) {
            if ($revertableReplace->getGame() === $replace->getGame()
                && $revertableReplace->getReplaced() === $replace->getReplaced()
                && $revertableReplace->getReplacement() === $replace->getReplacement()) {
                return true;
            }
        }
        return false;
    }

    protected function revertReplaces(): void
    {
        while (count($this->revertableReplaces) > 0) {
            $replace = array_pop($this->revertableReplaces);
            $replace->getGame()->setRefereePlaceUniqueIndex(null);
            $replace->getBatch()->removeReferee($replace->getReplacement());
            $replace->getGame()->setRefereePlaceUniqueIndex($replace->getReplaced());
            $replace->getBatch()->addRefereeUniqueIndex($replace->getReplaced());
        }
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
