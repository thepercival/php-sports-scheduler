<?php

declare(strict_types=1);

namespace SportsScheduler\Planning\Validator;

use SportsHelpers\SelfReferee;
use SportsPlanning\Resource\ResourceType;
use SportsScheduler\Exceptions\UnequalAssignedFieldsException;
use SportsScheduler\Exceptions\UnequalAssignedRefereePlacesException;
use SportsScheduler\Exceptions\UnequalAssignedRefereesException;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsScheduler\Resource\GameCounter\Unequal as UnequalGameCounter;
use SportsPlanning\Resource\ResourceCounter as ResourceCounterManager;
use stdClass;

class GameAssignments
{
    private ResourceCounterManager $counterManager;

    public function __construct(protected Planning $planning)
    {
        $this->counterManager = new ResourceCounterManager($planning);
    }




    public function validate(): void
    {
        if (!$this->planning->getInput()->hasMultipleSports()) {
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
        $nrOfPoules = $this->planning->getInput()->getPoules()->count();
        if ($this->planning->getInput()->getSelfReferee() === SelfReferee::SamePoule) {
            return true;
        }
        if (($this->planning->getInput()->getPlaces()->count() % $nrOfPoules) === 0) {
            return false;
        }
        if ($nrOfPoules === 2) {
            return true;
        }
        $input = $this->planning->getInput();
        if ($nrOfPoules > 2 && $input->selfRefereeEnabled()) {
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
        } elseif ($this->planning->getInput()->createPouleStructure()->isAlmostBalanced()) {
            $refereePlaceMap = $this->counterManager->getCounter(ResourceType::RefereePlaces);
            $unequal = $this->getMaxUnequal($refereePlaceMap);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    /**
     * @return array<int,array<string|int,PlaceGameCounter>>
     */
    protected function getRefereePlacesPerPoule(): array
    {
        $refereePlacesPerPoule = [];
        $refereePlaceMap = $this->counterManager->getCounter(ResourceType::RefereePlaces);
        /** @var PlaceGameCounter $gameCounter */
        foreach ($refereePlaceMap as $gameCounter) {
            /** @var Place $place */
            $place = $gameCounter->getResource();
            $pouleNr = $place->getPoule()->getNumber();
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
        return $retVal . '(' . $unequal . ')';
    }
}
