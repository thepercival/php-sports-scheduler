<?php

declare(strict_types=1);

namespace SportsScheduler\Resource\RefereePlace;

use SportsHelpers\SelfReferee;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Poule;

class Predicter
{
    private const int SAME_POULE_MAX_DELTA = 1;

    /**
     * @param list<Poule> $poules
     */
    public function __construct(protected array $poules)
    {
    }

    public function canStillAssign(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch, SelfReferee $selfReferee): bool
    {
        if ($selfReferee === SelfReferee::Disabled) {
            return true;
        }
        if ($selfReferee === SelfReferee::SamePoule) {
            return $this->validatePouleAssignmentsSamePoule($batch) && $this->validateTooMuchForcedAssignmentDiffernce(
                    $batch
                );
        }
        return $this->validatePouleAssignmentsOtherPoules($batch)
            && $this->validateTooMuchForcedAssignmentDiffernce($batch);
    }

    protected function validatePouleAssignmentsSamePoule(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): bool
    {
        $pouleCounterMap = $this->createGamePlacesCounterMap();
        $this->addGamesToPouleCounterMap($pouleCounterMap, $batch);

        foreach ($pouleCounterMap as $pouleCounter) {
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable([$pouleCounter]);
            if ($nrOfPlacesAvailable < $pouleCounter->getNrOfGames()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<int,GamePlacesCounterForPoule>
     */
    protected function createGamePlacesCounterMap(): array
    {
        $pouleCounterMap = [];
        foreach ($this->poules as $poule) {
            $pouleCounterMap[$poule->pouleNr] = new GamePlacesCounterForPoule($poule);
        }
        return $pouleCounterMap;
    }

    /**
     * @param array<int,GamePlacesCounterForPoule> $pouleCounterMap
     * @param SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch
     */
    protected function addGamesToPouleCounterMap(array $pouleCounterMap,
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): void
    {
        foreach ($batch->getBase()->getGames() as $game) {
            $pouleCounterMap[$game->poule->pouleNr]->add(count($game->getGamePlaces()));
        }
    }

    protected function validatePouleAssignmentsOtherPoules(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): bool
    {
        $pouleCounterMap = $this->createGamePlacesCounterMap();
        $this->addGamesToPouleCounterMap($pouleCounterMap, $batch);

        foreach ($pouleCounterMap as $pouleCounter) {
            $otherPouleCounters = array_values(
                array_filter(
                    $pouleCounterMap,
                    function (GamePlacesCounterForPoule $pouleCounterIt) use ($pouleCounter): bool {
                        return $pouleCounter !== $pouleCounterIt;
                    }
                )
            );
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable($otherPouleCounters);
            if ($pouleCounter->getNrOfGames() > $nrOfPlacesAvailable) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param list<GamePlacesCounterForPoule> $pouleCounters
     * @return int
     */
    protected function getNrOfPlacesAvailable(array $pouleCounters): int
    {
        $nrOfPlacesAvailable = 0;
        foreach ($pouleCounters as $pouleCounter) {
            $nrOfPlaces = count($pouleCounter->getPoule()->places);
            $nrOfPlacesAvailable += ($nrOfPlaces - $pouleCounter->calculateNrOfAssignedGamePlaces());
        }
        return $nrOfPlacesAvailable;
    }

    /**
     * voor selfref = samepoule , per plek kijken hoevaak deze verplicht is als scheidsrechter
     * dit mag max. het gemiddelde + 1.500000001 zijn
     */
    protected function validateTooMuchForcedAssignmentDiffernce(
        SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): bool
    {
        $totalNrOfForcedRefereePlaces = $batch->getTotalNrOfForcedRefereePlaces();
        $totalPouleCounters = $batch->getTotalPouleCounters();

        $pouleHasForcedRefereePlaces = function (Poule $poule) use ($totalNrOfForcedRefereePlaces): bool {
            foreach ($poule->places as $place) {
                if (array_key_exists($place->getUniqueIndex(), $totalNrOfForcedRefereePlaces)) {
                    return true;
                }
            }
            return false;
        };

        foreach ($this->poules as $poule) {
            if (count($totalNrOfForcedRefereePlaces) === 0 || !$pouleHasForcedRefereePlaces($poule)) {
                continue;
            }
            /** @var int|null $maxNrOfForcedRefereePlaces */
            $maxNrOfForcedRefereePlaces = null;
            /** @var int|null $minNrOfForcedRefereePlaces */
            $minNrOfForcedRefereePlaces = null;

            $avgNrOfGamesForRefereePlace = 0;
            if (array_key_exists($poule->pouleNr, $totalPouleCounters)) {
                $avgNrOfGamesForRefereePlace = $totalPouleCounters[$poule->pouleNr]->getNrOfGames(
                    ) / count($poule->places);
            }

            $pouleMax = $avgNrOfGamesForRefereePlace + self::SAME_POULE_MAX_DELTA;
            // $pouleMin = $avgNrOfGamesForRefereePlace - self::SAME_POULE_MAX_DELTA;

            // naast de forced referee assignments heb je ook dat places niet beschikbaar zijn, omdat ze zelf moeten
            // place met laagste nrOfForcedAssignment moet minimaal 1x beschikbaar zijn
            foreach ($poule->places as $place) {
                $nrOfForcedRefereePlaces = 0;
                if (array_key_exists($place->getUniqueIndex(), $totalNrOfForcedRefereePlaces)) {
                    $nrOfForcedRefereePlaces = $totalNrOfForcedRefereePlaces[$place->getUniqueIndex()];
                }
                if ($nrOfForcedRefereePlaces >= $pouleMax /*|| $nrOfForcedRefereePlaces <= $pouleMin*/) {
                    return false;
                }
                if ($minNrOfForcedRefereePlaces === null || $nrOfForcedRefereePlaces < $minNrOfForcedRefereePlaces) {
                    $minNrOfForcedRefereePlaces = $nrOfForcedRefereePlaces;
                }
                if ($maxNrOfForcedRefereePlaces === null || $nrOfForcedRefereePlaces > $maxNrOfForcedRefereePlaces) {
                    $maxNrOfForcedRefereePlaces = $nrOfForcedRefereePlaces;
                }
            }
            if ($maxNrOfForcedRefereePlaces !== null && $minNrOfForcedRefereePlaces !== null
                && ($maxNrOfForcedRefereePlaces - $minNrOfForcedRefereePlaces) > 1) {
                return false;
            }
        }
        return true;
    }
}
