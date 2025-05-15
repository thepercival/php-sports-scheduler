<?php

namespace SportsScheduler\Resource\Service;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\Input;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsOneWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;
use SportsScheduler\Resource\UniquePlacesCounter;
use SportsPlanning\Referee\PlanningRefereeInfo;

class SimCalculator
{
//    private PlanningRefereeInfo $refereeInfo;
    //private PouleStructure $pouleStructure;

    public function __construct()
    {
//        $this->refereeInfo = $input->getRefereeInfo();
        // $this->pouleStructure = $input->createPouleStructure();
        // $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();
        // $sportVariants = array_values($this->input->createSportVariants()->toArray());
        // $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
    }

//    public function getMaxNrOfGamesPerBatch(AssignPlanningCounters $assignPlanningCounters): int
//    {
//        $maxNrOfGamesPerBatch = 0;
//        foreach ($assignPlanningCounters as $sportInfo) {
//            $maxNrOfGamesPerBatch += $this->getMaxNrOfSimultaneousSportGames($sportInfo);
//        }
//        return $maxNrOfGamesPerBatch;
////        $maxNrOfGamesPerBatch = $this->inputCalculator->reduceByReferees($maxNrOfGamesPerBatch, $this->refereeInfo);
////        return $this->reduceByPlaces($maxNrOfGamesPerBatch, $infoToAssign);
//    }

//    public function getMaxNrOfSimultaneousSportGames(
//        PouleStructure $pouleStructure, SportWithNrOfFields $sportWithNrOfFields): int
//    {
//        // SHOULD THIS BE THE POULESTRUCTURE OF THIS??
//        // $pouleStructure = $this->createPouleStructureFromPoulesFromUniquePlaces($sportCountNrOfGamesAndUniquePlaces);
//        return $this->getMaxNrOfSportGamesPerBatch($pouleStructure, $sportWithNrOfFields);
//    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportWithNrOfFields> $sportsWithNrOfFields
     * @param PlanningRefereeInfo $refereeInfo
     * @return int
     * @throws \Exception
     */
    public function calculateMaxSimNrOfGames(
        PouleStructure $pouleStructure,
        array $sportsWithNrOfFields,
        PlanningRefereeInfo $refereeInfo): int {
        return array_sum(
            array_map( function( SportWithNrOfFields $sportWithNrOfFields ) use ($pouleStructure, $refereeInfo): int {
                return $this->calculateMaxSimNrOfSportGames(
                    $pouleStructure, $sportWithNrOfFields, $refereeInfo);
            }, $sportsWithNrOfFields )
        );
    }

    // per poule kijk je wat het maximum is en daar neem je de laagste waarde van
    public function calculateMaxSimNrOfSportGames(
        PouleStructure $pouleStructure,
        SportWithNrOfFields $sportWithNrOfFields,
        PlanningRefereeInfo $refereeInfo): int {
        $selfRefereeInfo = $refereeInfo->selfRefereeInfo;
        $minNrOfGamesPerBatch = array_sum(
            array_map( function( int $nrOfPlaces ) use ($sportWithNrOfFields, $selfRefereeInfo): int {
                $sport = $sportWithNrOfFields->sport;

                $sportWithNrOfPlaces = (new SportWithNrOfPlacesCreator())->create($nrOfPlaces, $sport);
                return $this->getMaxNrOfGamesSimultaneously($sportWithNrOfPlaces, $selfRefereeInfo);
            }, $pouleStructure->toArray() )
        );
        if ($sportWithNrOfFields->nrOfFields < $minNrOfGamesPerBatch) {
            $minNrOfGamesPerBatch = $sportWithNrOfFields->nrOfFields;
        }
        if ($selfRefereeInfo->selfReferee === SelfReferee::Disabled
            && $refereeInfo->nrOfReferees > 0
            && $refereeInfo->nrOfReferees < $minNrOfGamesPerBatch) {
            $minNrOfGamesPerBatch = $refereeInfo->nrOfReferees;
        }
        return $minNrOfGamesPerBatch;
    }

//    public function reduceByPlaces(int $maxNrOfGamesPerBatch, InfoToAssign $infoToAssign): int
//    {
//        $nrOfGamesPerBatch = 0;
//        foreach ($this->pouleStructure->toArray() as $nrOfPlaces) {
//            foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
//                $nrOfGamePlaces = $this->getNrOfGamePlaces($sportInfo->getSport()->createVariant(), $nrOfPlaces);
//                if ($nrOfGamePlaces <= $nrOfPlaces) {
//                    $nrOfGamesPerBatch++;
//                    $nrOfPlaces -= $nrOfGamePlaces;
//                }
//            }
//        }
//        return $nrOfGamesPerBatch < $maxNrOfGamesPerBatch ? $nrOfGamesPerBatch : $maxNrOfGamesPerBatch;
//    }

//
//    // uitgaan van het aantal wedstrijden en velden per sport en scheidsrechters
//    // aantal pouleplekken niet, want je kunt verschillende poules hebben met
//    // verschillende aantallen

//    public function getMinNrOfBatchesNeeded(SportWithNrOfFieldsCountNrOfGamesAndUniquePlaces $sportInfoTo): int
//    {
//        $maxNrOfSimultaneousGames = $this->getMaxNrOfSimultaneousSportGames($sportInfoTo);
//        return (int)ceil($sportInfoTo->getNrOfGames() / $maxNrOfSimultaneousGames);
//    }




//    /**
//     * @param array<int, SportInfo> $gameCounters
//     * @return list<PouleCounter>
//     */
//    protected function getGameCountersByLeastNrOfPoulePlaces(array $gameCounters): array
//    {
//        uasort(
//            $gameCounters,
//            function (PouleCounter $counterA, PouleCounter $counterB): int {
//                $nrOfPoulePlacesA = count($counterA->getPoule()->getPlaces());
//                $nrOfPoulePlacesB = count($counterB->getPoule()->getPlaces());
//                return $nrOfPoulePlacesA < $nrOfPoulePlacesB ? -1 : 1;
//            }
//        );
//        return array_values($gameCounters);
//    }

//    // @TODO CDK HOUD REKENING MET SELFREFERE OTHER POULE
//    protected function getMaxNrOfSimultaneousPouleGames(Sport $sport, int $nrOfPlaces): int
//    {
//        $nrOfGamesOneGameRound = $sport->createVariant()->getNrOfGamesOneGameRound($nrOfPlaces);
////        if (!array_key_exists($sport->getNumber(), $this->maxNrOfSimultanousGames)) {
////            $this->maxNrOfSimultanousGames[$sport->getNumber()] = [];
////        }
////        if (array_key_exists($nrOfPlaces, $this->maxNrOfSimultanousGames[$sport->getNumber()])) {
////            return $this->maxNrOfSimultanousGames[$sport->getNumber()][$nrOfPlaces];
////        }
//
//        $max = $this->getMaxNrOfSimultanousGamesHelper($sport, $nrOfPlaces);
//        $this->maxNrOfSimultanousGames[$sport->getNumber()][$nrOfPlaces] = $max;
//        return $max;
//    }
//
//    protected function getMaxNrOfSimultanousGamesForNrOfPlaces(Sport $sport, int $nrOfPlaces): int
//    {
//        // aantal wedstrijden per batch
//        $selfRefereeSamePoule = $this->selfReferee === SelfReferee::SamePoule;
//        $sportVariant = $sport->createVariant();
//        $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $nrOfPlaces, $selfRefereeSamePoule);
//
//        $maxGames = (int)floor($nrOfPlaces / $nrOfGamePlaces);
//        if ($sport->getFields()->count() < $maxGames) {
//            $maxGames = $sport->getFields()->count();
//        }
//
//        return $maxGames;
//    }
//
//    public function getNrOfGamePlaces(
//        SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant $sportVariant,
//        int $nrOfPlaces,
//        bool $selfRefereeSamePoule
//    ): int {
//        if ($sportVariant instanceof AgainstSportVariant) {
//            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
//        } elseif ($sportVariant instanceof SingleSportVariant) {
//            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
//        }
//        return $nrOfPlaces;
//    }

    // a eigen scheidsrechters

    // HET AANTAL BIJ MEERDERE POULES MET VERSCHILLENDE GROOTTES WIL JE HET MAX BEREKENING
    // DAARBIJ IS HET BELANGRIJK OM AAN TE GEVEN ALS DIT EEN MAXIMUM IS DIE HAALBAAR IS VOOR DE ALLE ITERATIES!!

    // VOORBEELD
    // BIJ [6,5] EN SportAgainst MET 2 VELDEN GEBRUIK PLANNINGPOULESTRUCTURE
    // BIJ NrOfPlaces 5 Kun je niet Alleen iets met SAMEPOULE ]


    // maxNrOfGamePlacesSimultaneouslyPossible, against
    // bij eigen scheids uit eigen poule
    //  a1 ja, andere poule dan meetellen voor desbetreffende poule
    //          a1a eigen poule scheids ontvangen  : altijd verzekerd, bij
    //          a1b eigen poule scheids leveren     : hoeveelheid kan verschillen
    //  a2 nee, eigen poule scheids ontvangen  : kan niet
    //          eigen poule scheids leveren     : hoeveelheid kan verschillen
    //
    public function getMaxNrOfGamesSimultaneously(
        TogetherSportWithNrOfPlaces|
        AgainstOneVsOneWithNrOfPlaces|AgainstOneVsTwoWithNrOfPlaces|AgainstTwoVsTwoWithNrOfPlaces $sportWithNrOfPlaces,
        SelfRefereeInfo $selfRefereeInfo): int {

        $nrOfPlaces = $sportWithNrOfPlaces->nrOfPlaces;

        $nrOfGamePlaces = $sportWithNrOfPlaces->sport->getNrOfGamePlaces();
        if( $sportWithNrOfPlaces instanceof TogetherSportWithNrOfPlaces) {
            if( $nrOfGamePlaces === null) {
                return 1;
            }
        }
        if( $nrOfGamePlaces === null) {
            throw new \Exception('nrOfGamePlaces cannot be 0');
        }
        if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs === 1) {
            $nrOfSimGames = (int)floor($nrOfPlaces / ($nrOfGamePlaces + 1));
        } else if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs > 1) {
            $nrOfSimGames = (int)floor(($nrOfPlaces - 1) / $nrOfGamePlaces);
        } else {
            $nrOfSimGames = (int)floor($nrOfPlaces / $nrOfGamePlaces);
        }
        return $nrOfSimGames === 0 ? 1 : $nrOfSimGames;
    }

//    public function createPouleStructureFromPoulesFromUniquePlaces(
//        SportWithNrOfFieldsCountNrOfGamesAndUniquePlaces $sportCountNrOfGamesAndUniquePlaces
//    ): PouleStructure
//    {
//        /** @var list<int> $nrOfPlacesPerPoule */
//        $nrOfPlacesPerPoule = array_map(function (UniquePlacesCounter $uniquePlacesCounter): int {
//            return count($uniquePlacesCounter->getPoule()->getPlaces());
//        }, array_values( $sportCountNrOfGamesAndUniquePlaces->getUniquePlacesCounterMap()) );
//        return new PouleStructure(...$nrOfPlacesPerPoule);
//    }
}
