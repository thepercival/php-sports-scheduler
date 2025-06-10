<?php

declare(strict_types=1);

namespace SportsScheduler\Iterators;

use SportsHelpers\PouleStructures\BalancedPouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Exceptions\SelfRefereeIncompatibleWithPouleStructureException;
use SportsPlanning\Exceptions\SportsIncompatibleWithPouleStructureException;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\Iterators\SportsIterators\AgainstSportIterator;
use SportsScheduler\Iterators\SportsIterators\SportIterator;
use SportsScheduler\Iterators\SportsIterators\TogetherSportIterator;

/**
 * @template TKey
 * @template TValue
 * @implements \Iterator<TKey, TValue>
 */
final class PlanningConfigurationIterator implements \Iterator
{
    protected BalancedPouleStructureIterator $structureIterator;
    protected SportIterator $sportIterator;
    protected SportRange $rangeNrOfReferees;
    protected SelfRefereeValidator $selfRefereeValidator;
    protected int $nrOfReferees;
    protected SelfReferee|null $selfReferee;
    protected PlanningConfiguration|null $current = null;

    public function __construct(
        SportRange $rangePlaces,
        SportRange $rangePlacesPerPoule,
        SportRange $rangePoules,
        SportRange $rangeNrOfReferees,
        SportRange $rangeNrOfFields,
        SportRange $rangeNrOfAgainstCycles,
        SportRange $rangeNrOfTogetherCycles,
        SportRange $rangeNrOfTogetherGamePlaces
    ) {
        $this->structureIterator = new BalancedPouleStructureIterator($rangePlaces, $rangePlacesPerPoule, $rangePoules);
        $this->sportIterator = new SportIterator(
            $rangeNrOfFields,
            $rangeNrOfAgainstCycles,
            $rangeNrOfTogetherCycles,
            $rangeNrOfTogetherGamePlaces
        );
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->selfRefereeValidator = new SelfRefereeValidator();
        $this->rewind();
    }

    protected function rewindStructure(): void
    {
        $this->rewindSport();
    }

    protected function rewindSport(): void
    {
        $this->sportIterator->rewind();
        $this->rewindNrOfReferees();
    }

    protected function rewindNrOfReferees(): void
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->getMin();
        $this->rewindSelfReferee();
    }

    protected function rewindSelfReferee(): void
    {
        $this->selfReferee = null;
    }

    #[\Override]
    public function current(): ?PlanningConfiguration
    {
        return $this->current;
    }

    #[\Override]
    public function key(): string
    {
        $planningInputOutput = new PlanningOutput();
        if ($this->current === null) {
            return 'no current value';
        }
        return $planningInputOutput->getConfigurationAsString($this->current);
    }

    #[\Override]
    public function next(): void
    {
        if ($this->current === null) {
            return;
        }

        if ($this->incrementValue() === false) {
            $this->current = null;
            return;
        }

        $pouleStructure = $this->structureIterator->current();
        $sportWithNrOfFieldsAndNrOfCycles = $this->sportIterator->current();
        if ($pouleStructure === null || $sportWithNrOfFieldsAndNrOfCycles === null) {
            $this->current = null;
            return;
        }
        try {
            $this->current = $this->createPlanningConfiguration($pouleStructure, $sportWithNrOfFieldsAndNrOfCycles);
        } catch(SelfRefereeIncompatibleWithPouleStructureException) {
        } catch(SportsIncompatibleWithPouleStructureException ) {

        }


//        $maxNrOfRefereesInPlanning = $planningInput->getMaxNrOfBatchGames(
//            Resources::FIELDS + Resources::PLACES
//        );
//        if ($this->nrOfReferees < $this->nrOfFields && $this->nrOfReferees > $maxNrOfRefereesInPlanning) {
//            if ($this->incrementNrOfFields() === false) {
//                return;
//            }
//            $this->current = $this->createInput();
//        }
//
//        $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//            Resources::REFEREES + Resources::PLACES
//        );
//        if ($this->nrOfFields < $this->nrOfReferees && $this->nrOfFields > $maxNrOfFieldsInPlanning) {
//            if ($this->incrementNrOfSports() === false) {
//                return;
//            }
//            $this->current = $this->createInput();
//        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->rewindStructure();
        $pouleStructure = $this->structureIterator->current();
        $sportWithNrOfFieldsAndNrOfCycles = $this->sportIterator->current();

        if ($pouleStructure === null || $sportWithNrOfFieldsAndNrOfCycles === null) {
            return;
        }
        $this->current = $this->createPlanningConfiguration($pouleStructure, $sportWithNrOfFieldsAndNrOfCycles);
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->current !== null;
    }

    protected function createPlanningConfiguration(
        BalancedPouleStructure $pouleStructure,
        SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles
    ): PlanningConfiguration {
        return new PlanningConfiguration(
            $pouleStructure,
            [$sportWithNrOfFieldsAndNrOfCycles],
            $this->selfReferee === null ? RefereeInfo::fromNrOfReferees($this->nrOfReferees) : RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo($this->selfReferee)),
            false
        );
    }

    protected function incrementValue(): bool
    {
        return $this->incrementSelfReferee();
    }

    protected function incrementSelfReferee(): bool
    {
        if ($this->nrOfReferees > 0 || $this->selfReferee === SelfReferee::SamePoule) {
            return $this->incrementNrOfReferees();
        }
        $pouleStructure = $this->structureIterator->current();
        $sportWithNrOfFieldsAndNrOfCycles = $this->sportIterator->current();
        if ($pouleStructure === null || $sportWithNrOfFieldsAndNrOfCycles === null) {
            return $this->incrementNrOfReferees();
        }
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        $selfRefereeIsAvailable = $this->selfRefereeValidator->canSelfRefereeBeAvailable($pouleStructure, [$sport]);
        if ($selfRefereeIsAvailable === false) {
            return $this->incrementNrOfReferees();
        }
        if ($this->selfReferee === null) {
            if ($this->selfRefereeValidator->canSelfRefereeOtherPoulesBeAvailable($pouleStructure)) {
                $this->selfReferee = SelfReferee::OtherPoules;
            } else {
                $this->selfReferee = SelfReferee::SamePoule;
            }
        } else {
            $selfRefereeSamePouleAvailable = $this->selfRefereeValidator->canSelfRefereeSamePouleBeAvailable(
                $pouleStructure, [$sport]
            );
            if (!$selfRefereeSamePouleAvailable) {
                return $this->incrementNrOfReferees();
            }
            $this->selfReferee = SelfReferee::SamePoule;
        }
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->getMax();
        $pouleStructure = $this->structureIterator->current();
        if ($pouleStructure === null) {
            return $this->incrementSport();
        }
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
        $maxNrOfRefereesByPlaces = (int)(ceil($nrOfPlaces / 2));
        if ($this->nrOfReferees >= $maxNrOfReferees || $this->nrOfReferees >= $maxNrOfRefereesByPlaces) {
            return $this->incrementSport();
        }
        $this->nrOfReferees++;
        $this->rewindSelfReferee();
        return true;
    }

    protected function incrementSport(): bool
    {
        $this->sportIterator->next();
        $sportWithNrOfFieldsAndNrOfCycles = $this->sportIterator->current();
        if ($sportWithNrOfFieldsAndNrOfCycles === null) {
            return $this->incrementStructure();
        }
        $sport = $sportWithNrOfFieldsAndNrOfCycles->sport;
        $pouleStructure = $this->structureIterator->current();
        if ($pouleStructure === null) {
            return $this->incrementStructure();
        }

        $this->rewindNrOfReferees();
        return true;
    }

    protected function incrementStructure(): bool
    {
        $this->structureIterator->next();
        if (!$this->structureIterator->valid()) {
            return false;
        }

        $this->rewindSport();
        return true;
    }

    /*if ($nrOfCompetitors === 6 && $nrOfPoules === 1 && $nrOfSports === 1 && $nrOfFields === 2
        && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
        $w1 = 1;
    } else*/ /*if ($nrOfCompetitors === 12 && $nrOfPoules === 2 && $nrOfSports === 1 && $nrOfFields === 4
            && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
            $w1 = 1;
        } else {
            continue;
        }*/

//        $multipleSports = count($sportConfig) > 1;
//        $newNrOfHeadtohead = $nrOfHeadtohead;
//        if ($multipleSports) {
//            //                                    if( count($sportConfig) === 4 && $sportConfig[0]["nrOfFields"] == 1 && $sportConfig[1]["nrOfFields"] == 1
//            //                                        && $sportConfig[2]["nrOfFields"] == 1 && $sportConfig[3]["nrOfFields"] == 1
//            //                                        && $teamup === false && $selfReferee === false && $nrOfHeadtohead === 1 && $structureConfig == [3]  ) {
//            //                                        $e = 2;
//            //                                    }
//            $newNrOfHeadtohead = $this->planningInputSerivce->getSufficientNrOfHeadtohead(
//                $nrOfHeadtohead,
//                min($structureConfig),
//                $teamup,
//                $selfReferee,
//                $sportConfig
//            );
//        }

//        $planningInput = new PlanningInput(
//            $structureConfig,
//            $sportConfig,
//            $nrOfReferees,
//            $teamup,
//            $selfReferee,
//            $newNrOfHeadtohead
//        );
//
//        if (!$multipleSports) {
//            $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//                Resources::REFEREES + Resources::PLACES
//            );
//            if ($nrOfFields > $maxNrOfFieldsInPlanning) {
//                return;
//            }
//        } else {
//            if ($nrOfFields > self::MAXNROFFIELDS_FOR_MULTIPLESPORTS) {
//                return;
//            }
//        }
}
