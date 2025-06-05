<?php

declare(strict_types=1);

namespace SportsScheduler\Planning\Validator;

use SportsHelpers\SelfReferee;
use SportsPlanning\Resource\GameCounter\GameCounterForPlace;
use SportsPlanning\Resource\ResourceType;
use SportsScheduler\Exceptions\UnequalAssignedFieldsException;
use SportsScheduler\Exceptions\UnequalAssignedRefereePlacesException;
use SportsScheduler\Exceptions\UnequalAssignedRefereesException;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
use SportsScheduler\Resource\GameCounter\Unequal as UnequalGameCounter;
use SportsPlanning\Resource\ResourceCounter as ResourceCounterManager;
use stdClass;

final class GameAssignments
{
    private ResourceCounterManager $counterManager;

    public function __construct(protected Planning $planning)
    {
        $this->counterManager = new ResourceCounterManager($planning);
    }




    public function validate(): void
    {
        if (count($this->planning->sports) === 1) {
            $fieldMap = $this->counterManager->getCounter(ResourceType::Fields);
            $unequalFields = $this->getMaxUnequal($fieldMap);
            if ($unequalFields !== null) {
                throw new UnequalAssignedFieldsException($this->getUnequalDescription($unequalFields, "fields"), E_ERROR);
            }
        }

        $refereeMap = $this->counterManager->getCounter(ResourceType::Referees);
        $unequalReferees = $this->getMaxUnequal($refereeMap);
        if ($unequalReferees !== null) {
            throw new UnequalAssignedRefereesException(
                $this->getUnequalDescription($unequalReferees, "referees"),
                E_ERROR
            );
        }

        $unequalRefereePlaces = $this->getRefereePlaceUnequals();
        if (count($unequalRefereePlaces) > 0) {
            throw new UnequalAssignedRefereePlacesException(
                $this->getUnequalDescription(reset($unequalRefereePlaces), "refereePlaces"),
                E_ERROR
            );
        }
    }

    protected function shouldValidatePerPoule(): bool
    {
        $selfRefereeInfo = $this->planning->getConfiguration()->refereeInfo->selfRefereeInfo;

        $nrOfPoules = count($this->planning->poules);
        if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule) {
            return true;
        }
        if (($this->planning->getNrOfPlaces() % $nrOfPoules) === 0) {
            return false;
        }
        if ($nrOfPoules === 2) {
            return true;
        }

        if ($nrOfPoules > 2 && $selfRefereeInfo->selfReferee !== SelfReferee::Disabled) {
            return true;
        }
        return false;
    }

    /**
     * @return list<UnequalGameCounter>
     */
    public function getRefereePlaceUnequals(): array
    {
        $unequals = [];
        if ($this->shouldValidatePerPoule()) {
            $refereePlacesPerPoule = $this->getRefereePlacesPerPoule();
            foreach ($refereePlacesPerPoule as $pouleNr => $refereePlaces) {
                $unequal = $this->getMaxUnequal($refereePlaces);
                if ($unequal !== null) {
                    $unequal->setPouleNr($pouleNr);
                    $unequals[] = $unequal;
                }
            }
        } elseif ($this->planning->getConfiguration()->pouleStructure->isAlmostBalanced()) {
            $refereePlaceMap = $this->counterManager->getCounter(ResourceType::RefereePlaces);
            $unequal = $this->getMaxUnequal($refereePlaceMap);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    /**
     * @return array<int,array<string|int,GameCounterForPlace>>
     */
    protected function getRefereePlacesPerPoule(): array
    {
        $refereePlacesPerPoule = [];
        $refereePlaceMap = $this->counterManager->getCounter(ResourceType::RefereePlaces);
        /** @var GameCounterForPlace $gameCounter */
        foreach ($refereePlaceMap as $gameCounter) {
            /** @var Place $place */
            $place = $gameCounter->getResource();
            $pouleNr = $place->pouleNr;
            if (!array_key_exists($pouleNr, $refereePlacesPerPoule)) {
                $refereePlacesPerPoule[$pouleNr] = [];
            }
            $refereePlacesPerPoule[$pouleNr][$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlacesPerPoule;
    }

    /**
     * @param array<int|string,GameCounter> $gameCounters
     * @return UnequalGameCounter|null
     */
    protected function getMaxUnequal(array $gameCounters): UnequalGameCounter|null
    {
        $data = $this->setCounters($gameCounters);
        /** @var int|null $minNrOfGames */
        $minNrOfGames = $data->minNrOfGames;
        /** @var int|null $maxNrOfGames */
        $maxNrOfGames = $data->maxNrOfGames;
        /** @var array<int|string,GameCounter> $maxGameCounters */
        $maxGameCounters = $data->maxGameCounters;

        if ($minNrOfGames === null || $maxNrOfGames === null || $maxNrOfGames - $minNrOfGames <= 1) {
            return null;
        }
        $otherGameCounters = array_filter($gameCounters, function (GameCounter $gameCounterIt) use ($maxNrOfGames): bool {
            return ($gameCounterIt->getNrOfGames() + 1) < $maxNrOfGames;
        });
//        uasort($otherGameCounters, function (GameCounter $a, GameCounter $b): int {
//            return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
//        });
        return new UnequalGameCounter(
            $minNrOfGames,
            $otherGameCounters,
            $maxNrOfGames,
            $maxGameCounters
        );
    }

    /**
     * @param array<int|string,GameCounter> $gameCounters
     * @return stdClass
     */
    private function setCounters(array $gameCounters): stdClass
    {
        $minNrOfGames = null;
        $maxNrOfGames = null;
        $maxGameCounters = [];
        foreach ($gameCounters as $gameCounter) {
            $nrOfGames = $gameCounter->getNrOfGames();
            if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames >= $maxNrOfGames) {
                if ($nrOfGames > $maxNrOfGames) {
                    $maxGameCounters = [];
                }
                $maxGameCounters[$gameCounter->getIndex()] = $gameCounter;
                $maxNrOfGames = $nrOfGames;
            }
        }
        $data = new stdClass();
        $data->minNrOfGames = $minNrOfGames;
        $data->maxNrOfGames = $maxNrOfGames;
        $data->maxGameCounters = $maxGameCounters;
        return $data;
    }

    protected function getUnequalDescription(UnequalGameCounter $unequal, string $suffix): string
    {
        $retVal = "too much difference(" . $unequal->getDifference() . ") in number of games for " . $suffix;
        return $retVal . '(' .(string)$unequal . ')';
    }
}
