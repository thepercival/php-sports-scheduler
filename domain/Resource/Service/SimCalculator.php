<?php

namespace SportsScheduler\Resource\Service;

use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\Variant as SportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsPlanning\Input;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\AllInOneGame as AllInOneGameWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Single as SingleWithNrOfPlaces;
use SportsScheduler\Resource\UniquePlacesCounter;
use SportsPlanning\Referee\Info as RefereeInfo;

class SimCalculator
{
    private RefereeInfo $refereeInfo;
    private PouleStructure $pouleStructure;

    public function __construct(Input $input)
    {
        $this->refereeInfo = $input->getRefereeInfo();
        $this->pouleStructure = $input->createPouleStructure();
        // $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();
        // $sportVariants = array_values($this->input->createSportVariants()->toArray());
        // $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
    }

    public function getMaxNrOfGamesPerBatch(InfoToAssign $infoToAssign): int
    {
        $maxNrOfGamesPerBatch = 0;
        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            $maxNrOfGamesPerBatch += $this->getMaxNrOfSimultaneousSportGames($sportInfo);
        }
        return $maxNrOfGamesPerBatch;
//        $maxNrOfGamesPerBatch = $this->inputCalculator->reduceByReferees($maxNrOfGamesPerBatch, $this->refereeInfo);
//        return $this->reduceByPlaces($maxNrOfGamesPerBatch, $infoToAssign);
    }

    public function getMaxNrOfSimultaneousSportGames(SportInfo $sportInfo): int
    {
        // SHOULD THIS BE THE POULESTRUCTURE OF THIS??
        $pouleStructure = $this->getPouleStructureFromPoulesToAssign($sportInfo);
        $sportVariantWithFields = new SportVariantWithFields($sportInfo->getVariant(), $sportInfo->getSport()->getNrOfFields());

        return $this->getMaxNrOfSportGamesPerBatch($pouleStructure, $sportVariantWithFields);
    }

    // per poule kijk je wat het maximum is en daar neem je de laagste waarde van
    public function getMaxNrOfSportGamesPerBatch(
        PouleStructure $pouleStructure, SportVariantWithFields $sportVariantWithFields): int {
        $selfRefereeInfo = $this->refereeInfo->selfRefereeInfo;
        $minNrOfGamesPerBatch = array_sum(
            array_map( function( int $nrOfPlaces ) use ($sportVariantWithFields, $selfRefereeInfo): int {
                $variantWithNrOfPlaces = (new VariantCreator())->createWithNrOfPlaces($nrOfPlaces, $sportVariantWithFields->getSportVariant());
                return $this->getMaxNrOfGamesSimultaneously($variantWithNrOfPlaces, $selfRefereeInfo);
            }, $pouleStructure->toArray() )
        );
        if ($sportVariantWithFields->getNrOfFields() < $minNrOfGamesPerBatch) {
            $minNrOfGamesPerBatch = $sportVariantWithFields->getNrOfFields();
        }
        if ($this->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::Disabled
            && $this->refereeInfo->nrOfReferees > 0
            && $this->refereeInfo->nrOfReferees < $minNrOfGamesPerBatch) {
            $minNrOfGamesPerBatch = $this->refereeInfo->nrOfReferees;
        }
        return $minNrOfGamesPerBatch;
    }


    /**
     * @param SportInfo $sportInfo
     * @return PouleStructure
     */
    protected function getPouleStructureFromPoulesToAssign(SportInfo $sportInfo): PouleStructure
    {
        /** @var list<int> $nrOfPlacesPerPoule */
        $nrOfPlacesPerPoule = array_map(function (UniquePlacesCounter $uniquePlacesCounter): int {
            return count($uniquePlacesCounter->getPoule()->getPlaces());
        }, $sportInfo->getUniquePlacesCounters());
        return new PouleStructure(...$nrOfPlacesPerPoule);
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

    protected function getNrOfGamePlaces(SportVariant $sportVariant, int $nrOfPlaces): int
    {
        if ($sportVariant instanceof SingleSportVariant || $sportVariant instanceof AgainstSportVariant) {
            return $sportVariant->getNrOfGamePlaces();
        }
        return $nrOfPlaces;
    }

//
//    // uitgaan van het aantal wedstrijden en velden per sport en scheidsrechters
//    // aantal pouleplekken niet, want je kunt verschillende poules hebben met
//    // verschillende aantallen

    public function getMinNrOfBatchesNeeded(SportInfo $sportInfoToAssign): int
    {
        $maxNrOfSimultaneousGames = $this->getMaxNrOfSimultaneousSportGames($sportInfoToAssign);
        return (int)ceil($sportInfoToAssign->getNrOfGames() / $maxNrOfSimultaneousGames);
    }




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
        AllInOneGameWithNrOfPlaces|SingleWithNrOfPlaces|AgainstH2hWithNrOfPlaces|AgainstGppWithNrOfPlaces $sportVariantWithNrOfPlaces,
        SelfRefereeInfo $selfRefereeInfo): int {
        if( $sportVariantWithNrOfPlaces instanceof AllInOneGameWithNrOfPlaces) {
            return 1;
        }
        $sportVariant = $sportVariantWithNrOfPlaces->getSportVariant();
        $nrOfPlaces = $sportVariantWithNrOfPlaces->getNrOfPlaces();
        $nrOfGamePlaces = $sportVariant->getNrOfGamePlaces();
        if( $sportVariantWithNrOfPlaces instanceof SingleWithNrOfPlaces) {
            if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs === 1) {
                $nrOfSimGames = (int)floor($nrOfPlaces / ($nrOfGamePlaces + 1));
            } else if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs > 1) {
                $nrOfSimGames = (int)floor(($nrOfPlaces - 1) / $nrOfGamePlaces);
            } else {
                $nrOfSimGames = (int)floor($nrOfPlaces / $nrOfGamePlaces);
            }
            return $nrOfSimGames === 0 ? 1 : $nrOfSimGames;
        }

        // als i
        if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs === 1) {
            $nrOfSimGames = (int)floor($nrOfPlaces / ($nrOfGamePlaces + 1));
        } else if ($selfRefereeInfo->selfReferee === SelfReferee::SamePoule && $selfRefereeInfo->nrIfSimSelfRefs > 1) {
            $nrOfSimGames = (int)floor(($nrOfPlaces - 1) / $nrOfGamePlaces);
        } else {
            $nrOfSimGames = (int)floor($nrOfPlaces / $nrOfGamePlaces);
        }
        return $nrOfSimGames === 0 ? 1 : $nrOfSimGames;
    }
}
