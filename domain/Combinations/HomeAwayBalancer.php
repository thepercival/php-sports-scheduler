<?php

namespace SportsScheduler\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\Amount\Range;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\HomeAwaySearcher;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Output\Combinations\HomeAwayOutput;
use SportsPlanning\Place;

class HomeAwayBalancer
{
    public function __construct(private LoggerInterface $logger)
    {
    }


    /**
     * @param SideNrCounterMap $homeNrCounterMapFromPreviousSports
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAways
     * @param SideNrCounterMap $awayNrCounterMapFromPreviousSports
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function balance2(
        SideNrCounterMap $homeNrCounterMapFromPreviousSports,
        Range $allowedHomeRange,
        SideNrCounterMap $awayNrCounterMapFromPreviousSports,
        array $sportHomeAways): array {
        $sportHomeAwaysAfterAdding = $this->addHomeAwaysToPreviousSports(
            clone $homeNrCounterMapFromPreviousSports,
            clone $awayNrCounterMapFromPreviousSports,
            $sportHomeAways
        );

        // $sportHomeAwaysAfterAdding
        // Adding => Start
        $homeNrCounterMapCumulative = clone $homeNrCounterMapFromPreviousSports;
        $homeNrCounterMapCumulative->addHomeAways($sportHomeAwaysAfterAdding);
        $awayNrCounterMapCumulative = clone $awayNrCounterMapFromPreviousSports;
        $awayNrCounterMapCumulative->addHomeAways($sportHomeAwaysAfterAdding);
        $rangedHomeNrCounterMapCumulative = new RangedPlaceNrCounterMap($homeNrCounterMapCumulative, $allowedHomeRange );
        if ( $rangedHomeNrCounterMapCumulative->withinRange(0) ) {
            return $sportHomeAwaysAfterAdding;
        }

        $homeCounterReportCumulative = $rangedHomeNrCounterMapCumulative->calculateReport();

        $homeNrCounterMapCumulative->output($this->logger, '', 'ha after swapping');
        (new HomeAwayOutput())->outputHomeAways($sportHomeAwaysAfterAdding,'ha after adding');

        if( $homeCounterReportCumulative->getTotalAboveMaximum() === 1 && $homeCounterReportCumulative->getTotalBelowMinimum() === 1 ) {
            $homeAwaysToSwap = $this->getHomeAwaysToSwapOneTooManyOneTooFew(
                $rangedHomeNrCounterMapCumulative, $sportHomeAwaysAfterAdding
            );
            $correctHomeAways = $sportHomeAwaysAfterAdding;
            $this->swapHomeAways($homeNrCounterMapCumulative, $awayNrCounterMapCumulative, $correctHomeAways, $homeAwaysToSwap);

//            $homeCounterMap = $this->createSideNrCounterMap(Side::Home, $correctHomeAways);


            $rangedHomeCounterMap = new RangedPlaceNrCounterMap($homeNrCounterMapCumulative, $allowedHomeRange );
            if ( !$rangedHomeCounterMap->withinRange(0) ) {
                throw new \Exception('should be in range');
            }
            return $correctHomeAways;

        }
        throw new \Exception('DO IMPLEMENT!');
//        $sportHomeAwaysAfterAdding = $this->calculateHomeAwaysForReversedHomeCounterMap(
//            $homeCounterMapFromPreviousSports,
//            $awayCounterMapFromPreviousSports,
//            $sportHomeAways
//        );
//    }
//        return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
        // Adding => End

//        $homeCounterMapForDebug = $this->createHomeCounterMapForDebug($sportHomeAwaysAfterAdding);
//        $homeCounterMapForDebug->output($this->logger, '', 'ha after adding');
////        (new HomeAwayOutput())->outputHomeAways($sportHomeAwaysAfterAdding,'ha after adding');
//
//        $homeCounterMap = (new CombinationMapper())->initAndFillSideCounterMap(Side::Home, $sportHomeAwaysAfterAdding);
//        $awayCounterMap = (new CombinationMapper())->initAndFillSideCounterMap(Side::Away, $sportHomeAwaysAfterAdding);
//        $rangedHomeCounterMap = new RangedPlaceCounterMap($homeCounterMap, $allowedHomeRange );
//        if( $rangedHomeCounterMap->withinRange(0) ) {
//            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
//        }
//        // Major Swap => Start
//        $sportHomeAwaysAfterMajorBalancing = $sportHomeAwaysAfterAdding;
////        $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($homeCounterMap, $sportHomeAwaysAfterMajorBalancing);
////        while ( count($homeAwaysToSwap) > 0) {
////            $this->swapHomeAways(
////                $homeCounterMap,
////                $awayCounterMap,
////                $sportHomeAwaysAfterMajorBalancing,
////                $homeAwaysToSwap);
////            $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($homeCounterMap, $sportHomeAwaysAfterMajorBalancing);
////            $we = 12;
////            $homeCounterMapForDebug = $this->createHomeCounterMapForDebug($sportHomeAwaysAfterMajorBalancing);
////            $homeCounterMapForDebug->output($this->logger, '', 'ha while major balancing');
////        }
////        $rangedHomeCounterMap = new RangedPlaceCounterMap($homeCounterMap, $allowedHomeRange );
////        if( $rangedHomeCounterMap->withinRange(0)
////            || $allowedHomeRange->getAmountDifference() > 0 ) {
////            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
////        }
//        // Major Swap => End
//
////        $homeCounterMapForDebug = $this->createHomeCounterMapForDebug($sportHomeAwaysAfterMajorBalancing);
////        $homeCounterMapForDebug->output($this->logger, '', 'ha after major, before minor');
//
//        // Minor Swap => Start
//        $nrOfHomeGames = $rangedHomeCounterMap->getAllowedRange()->getMax()->amount;
//        $sportHomeAwaysAfterMinorBalancing = $sportHomeAwaysAfterMajorBalancing;
//        $homeAwaysToSwap = $this->calculateHomeAwaysToSwap($nrOfHomeGames, $homeCounterMap, $sportHomeAwaysAfterMinorBalancing);
//        while ( $homeAwaysToSwap !== null ) {
//            $this->swapHomeAways(
//                $homeCounterMap,
//                $awayCounterMap,
//                $sportHomeAwaysAfterMinorBalancing,
//                $homeAwaysToSwap);
//            $homeAwaysToSwap = $this->calculateHomeAwaysToSwap($nrOfHomeGames, $homeCounterMap, $sportHomeAwaysAfterMinorBalancing);
//        }
//        // Minor Swap => End
//
//        $homeCounterMapForDebug = $this->createHomeCounterMapForDebug($sportHomeAwaysAfterMinorBalancing);
//        $homeCounterMapForDebug->output($this->logger, '', 'ha after minor');
//
//        return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterMinorBalancing );
    }

    /**
     * @param SidenrCounterMap $homeNrCounterMapFromPreviousSports
     * @param SidenrCounterMap $awayNrCounterMapFromPreviousSports
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    private function addHomeAwaysToPreviousSports(
        SideNrCounterMap $homeNrCounterMapFromPreviousSports,
        SideNrCounterMap $awayNrCounterMapFromPreviousSports,
        array $sportHomeAways): array {

        $newSportHomeAways = [];

        $homeCounterMap = clone $homeNrCounterMapFromPreviousSports;
        $awayCounterMap = clone $awayNrCounterMapFromPreviousSports;

//        $homeCounterMap->output($this->logger, '', 'ha assigned home totals');
//        $awayCounterMap->output($this->logger, '', 'ha assigned away totals');
//        (new HomeAwayOutput())->outputHomeAways($sportHomeAways, " sport ha left");
        $swapped = false;
        while( $bestSSportHomeAway = $this->getBestHomeAway($homeCounterMap, $awayCounterMap, $sportHomeAways,$swapped ) ) {
            $key = array_search($bestSSportHomeAway, $sportHomeAways, true);
            if( $key !== false ) {
                array_splice($sportHomeAways, $key, 1);
            }
            if( $swapped ) {
                $bestSSportHomeAway = $bestSSportHomeAway->swap();
            }
            $newSportHomeAways[] = $bestSSportHomeAway;
            $homeCounterMap->addHomeAway($bestSSportHomeAway);
            $awayCounterMap->addHomeAway($bestSSportHomeAway);
            (new HomeAwayOutput())->outputHomeAways($newSportHomeAways, " while loop");
        }
//        $homeCounterMap->output($this->logger, '', 'ha assigned home totals');
//        $awayCounterMap->output($this->logger, '', 'ha assigned away totals');
//        (new HomeAwayOutput())->outputHomeAways($newSportHomeAways, " sport ha assigned");
//        (new HomeAwayOutput())->outputHomeAways($sportHomeAways, "  ha left");
//        $this->logger->info("addHomeAwaysToExisting ENDED ENDED ENDED ENDED ENDED ENDED ENDED");
        return $newSportHomeAways;
    }

    /**
     * @param RangedPlaceNrCounterMap $rangedHomeNrCounterMap
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     * @throws \Exception
     */
    private function getHomeAwaysToSwapOneTooManyOneTooFew(
        RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
        array $homeAways): array {
        $placeNrsOneTimeTooManyHome = $rangedHomeNrCounterMap->getPlaceNrsAboveMaximum();
        $placeNrOneTimeTooManyHome = reset($placeNrsOneTimeTooManyHome);
        if( count($placeNrsOneTimeTooManyHome) !== 1 || $placeNrOneTimeTooManyHome === false) {
            throw new \Exception('there should be 1 place above the maximum');
        }
        $placeNrsOneTimeTooFewHome = $rangedHomeNrCounterMap->getPlaceNrsBelowMinimum();
        $placeNrOneTimeTooFewHome = reset($placeNrsOneTimeTooFewHome);
        if( count($placeNrsOneTimeTooFewHome) !== 1 || $placeNrOneTimeTooFewHome === false ) {
            throw new \Exception('there should be 1 place above the maximum');
        }

        $awayPlaceNrs = [$placeNrOneTimeTooManyHome, $placeNrOneTimeTooFewHome];
        $swappableHomeAways = $this->getHomeAwaysBySide($homeAways, Side::Away, $awayPlaceNrs);
        foreach( $swappableHomeAways as $swappableHomeAway) {
            $swappableHomeAwaysStepTwo = $this->getHomeAwaysToSwapOneTooManyOneTooFewStepTwo(
                $rangedHomeNrCounterMap, $homeAways, $swappableHomeAway->convertToPlaceNrs(Side::Home), $placeNrOneTimeTooManyHome
            );
            if( $swappableHomeAwaysStepTwo === null ) {
                continue;
            }
            return array_merge([$swappableHomeAway], $swappableHomeAwaysStepTwo);
        }
        throw new \Exception('other swappables should be found');
    }

    /**
     * @param RangedPlaceNrCounterMap $rangedHomeNrCounterMap
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param list<int> $previousHomePlaceNrs
     * @param int $placeNrOneTimeTooManyHome
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>|null
     * @throws \Exception
     */
    private function getHomeAwaysToSwapOneTooManyOneTooFewStepTwo(
        RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
        array $homeAways,
        array $previousHomePlaceNrs,
        int $placeNrOneTimeTooManyHome): array|null {

        $homeAwaysWithTooManyHome = $this->getHomeAwaysByPlaceNr($homeAways, Side::Home, $placeOneTimeTooManyHome);
        $firstPreviousHomePlace = $previousHomePlaces[0];
        $homeAwaysFirstPreviousHomePlace = $this->getHomeAwaysByPlaceNr($homeAwaysWithTooManyHome, Side::Away, $firstPreviousHomePlace);
        $secondPreviousHomePlace = $previousHomePlaces[1];
        $homeAwaysSecondPreviousHomePlace = $this->getHomeAwaysByPlaceNr($homeAwaysWithTooManyHome, Side::Away, $secondPreviousHomePlace);
        foreach( $homeAwaysFirstPreviousHomePlace as $homeAwayFirstPreviousHomePlace ) {
            foreach( $homeAwaysSecondPreviousHomePlace as $homeAwaySecondPreviousHomePlace ) {
                if( $homeAwayFirstPreviousHomePlace->getOtherSidePlace($placeOneTimeTooManyHome)
                === $homeAwaySecondPreviousHomePlace->getOtherSidePlace($secondPreviousHomePlace)
                && $homeAwayFirstPreviousHomePlace->getOtherSidePlace($firstPreviousHomePlace)
                    === $homeAwaySecondPreviousHomePlace->getOtherSidePlace($placeOneTimeTooManyHome)
                ) {
                    return [$homeAwayFirstPreviousHomePlace, $homeAwaySecondPreviousHomePlace];
                }
            }
        }
        return null;
    }

    /**
     * @param SideNrCounterMap $homeNrCounterMap
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    private function getHomeAwaysWithAtLeastTwoDifference(SideNrCounterMap $homeNrCounterMap, array $sportHomeAways): array {
        $filteredHomeAway = array_filter( $sportHomeAways, function(
            OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use ($homeNrCounterMap): bool {
            return $this->getHomeDifference($homeNrCounterMap, $homeAway) > 1;
        });
        uasort($filteredHomeAway, function (
            OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAwayA,
            OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAwayB) use ($homeNrCounterMap): int {
            return $this->getHomeDifference($homeNrCounterMap, $homeAwayA)
                - $this->getHomeDifference($homeNrCounterMap, $homeAwayB);
        });
        // $logger->info("sorted homeaways in " . (microtime(true) - $time_start));
        // (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
        return array_values($filteredHomeAway);
    }

    /**
     * @param PlaceNrCounterMap $homePlaceNrCounterMap
     * @param PlaceNrCounterMap $awayPlaceNrCounterMap
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAways
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAwaysToSwap
     * @return void
     */
    protected function swapHomeAways(
        PlaceNrCounterMap $homePlaceNrCounterMap,
        PlaceNrCounterMap $awayPlaceNrCounterMap,
        array &$sportHomeAways, array $homeAwaysToSwap): void {

        foreach( $homeAwaysToSwap as $homeAwayToSwap) {
            $key = array_search($homeAwayToSwap, $sportHomeAways, true);
            if( $key === false ) {
                continue;
            }
            array_splice($sportHomeAways, $key, 1);
            foreach( $homeAwayToSwap->getHome()->getPlaces() as $homePlace) {
                $homePlaceNrCounterMap->removePlace($homePlace);
            }
            foreach( $homeAwayToSwap->getAway()->getPlaces() as $awayPlace) {
                $awayPlaceNrCounterMap->removePlace($awayPlace);
            }
            $swappedHomeAway = $homeAwayToSwap->swap();
            foreach( $swappedHomeAway->getHome()->getPlaces() as $homePlace) {
                $homePlaceNrCounterMap->addPlace($homePlace);
            }
            foreach( $swappedHomeAway->getAway()->getPlaces() as $awayPlace) {
                $awayPlaceNrCounterMap->addPlace($awayPlace);
            }
            $sportHomeAways[] = $swappedHomeAway;
        }
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAways
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $newHomeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getSwapped(array $sportHomeAways, array $newHomeAways ): array {
        $swappedHomeAways = [];

        $nrOfSportHomeAways = count($sportHomeAways);
        foreach( $newHomeAways as $newHomeAway) {
            $count = 0;
            $sportHomeAway = array_shift($sportHomeAways);
            while( ++$count <= $nrOfSportHomeAways && $sportHomeAway !== null ) {
                if( $sportHomeAway->getIndex() === $newHomeAway->getIndex() ) {
                    break;
                }
                if( $sportHomeAway->getIndex() === $newHomeAway->swap()->getIndex() ) {
                    $swappedHomeAways[] = $newHomeAway;
                    break;
                }
                $sportHomeAways[] = $sportHomeAway;
                $sportHomeAway = array_shift($sportHomeAways);
            }
        }
        return $swappedHomeAways;
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getSwappedHomeAways(array $homeAways): array {
        return array_map(function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
            return $homeAway->swap();
        }, $homeAways );
    }

//    /**
//     * @param int $avarageNrOfHomeGames
//     * @param PlaceCounterMap $homeCounterMap
//     * @param list<HomeAway> $homeAways
//     * @return list<HomeAway>|null
//     */
//    protected function calculateHomeAwaysForReversedHomeCounterMap(
//        int $avarageNrOfHomeGames,
//        PlaceCounterMap $homeCounterMap,
//        array $homeAways): array|null {
//
//        $reversedHomeCounterMap = $this->reverseHomeCounterMap($homeCounterMap);

        // voer weer

        // als de 2 plaatsen tegen elkaar spelen doe dan
        // zoek een aantal wedstrijden waarbij dezelfde situatie ontstaat, maar dan andersom
        // als deze wedstrijden moeten dan geswapped worden


//        $homeCounterReport = $homeCounterMap->calculateReport();
//        $diffWithMax = $homeCounterReport->getMaxAmount() - $avarageNrOfHomeGames;
//        $diffWithMin = $avarageNrOfHomeGames - $homeCounterReport->getMaxAmount();
//        $minNrOfGamesToSwap = max($diffWithMax, $diffWithMin);
//
//        $homeCounterReport->getAmountDifference()
//
//        if( $homeCounterReport->amountAbove($avarageNrOfHomeGames) === 1 &&
//            $homeCounterReport->amountBeneath($avarageNrOfHomeGames) === 1 ) {
//            if( not 2vs2 than ) {
//                throw new \Exception('works only for 2vs2, implement for 1vs1 and 1vs2 ');
//            }
//
//
//
//            $minNrOfGamesToSwap = max($diffWithMax, $diffWithMin);
//            $minNrOfGamesToSwap = 3;
//        }
//
//
//
//        // bereken het aantal plekken die meer thuis hebben gespeeld
//        // zoek hier dan een wedstrijd voor
//        // als dit 1 is en het is 2vs2 dan zijn er minimaal 2 wedstrijden nodig
//
//
//        // als verschil onder en boven
//        disbalans koppelen aan (1vs1, 1vs2 en 2vs2) om te kijken hoeveel
//
//        // -- SITUATIE 1 : 15 15 15 16 14
//        // -- SITUATIE 2 : 15 16 14 16 14
//        // -- SITUATIE 3 : 15 15 14 17 14 DEZE KOMT MISSCHIEN NIET VOOR
//
//        kijk eerst hoeveel swaps je minimaal nodig bent om  (1vs1, 1vs2 en 2vs2) de balance weer gelijk
//
//        $homeCounterReport = $homeCounterMap->calculateReport();
//        $greater = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames + 1);
//        $greater = array_shift($greater);
//        if( $greater === null ) {
//            return null;
//        }
//        $equal = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames);
//        if( count($equal) === 0 ) {
//            return null;
//        }
//        $smaller = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames - 1);
//        $smaller = array_shift($smaller);
//        if( $smaller === null ) {
//            return null;
//        }
//
//        // Filter all homeaways which have places which have more than average homeCounts
//        $greaterHomeHomeAways = $this->getHomeAwaysWithPlaceAtSide(Side::Home, $greater, $homeAways);
//        // Filter all homeaways which have places which have more than average homeCounts
//        $greaterNotInHome = $this->getHomeAwaysWithPlaceNotAtSide(Side::Home, $greater, $homeAways);
//        $greaterInAway = $this->getHomeAwaysWithPlaceNotAtSide(Side::Home, $greater, $homeAways);
//        $maxRouteLength = 10;
//        return $this->getSwapRouteHelper($greaterHomeHomeAways, $otherHomeAways, $smaller, [], $maxRouteLength);
//    }

//    /**
//     * @param int $avarageNrOfHomeGames
//     * @param PlaceCounterMap $homeCounterMap
//     * @param list<HomeAway> $homeAways
//     * @return list<HomeAway>|null
//     */
//    protected function calculateHomeAwaysToSwap(
//        int $avarageNrOfHomeGames,
//        PlaceCounterMap $homeCounterMap,
//        array $homeAways): array|null {
//
//        // als de 2 plaatsen tegen elkaar spelen doe dan
//        // zoek een aantal wedstrijden waarbij dezelfde situatie ontstaat, maar dan andersom
//        // als deze wedstrijden moeten dan geswapped worden
//
//
//        $homeCounterReport = $homeCounterMap->calculateReport();
//        $diffWithMax = $homeCounterReport->getMaxAmount() - $avarageNrOfHomeGames;
//        $diffWithMin = $avarageNrOfHomeGames - $homeCounterReport->getMaxAmount();
//        $minNrOfGamesToSwap = max($diffWithMax, $diffWithMin);
//
//        $homeCounterReport->getAmountDifference()
//
//        if( $homeCounterReport->amountAbove($avarageNrOfHomeGames) === 1 &&
//            $homeCounterReport->amountBeneath($avarageNrOfHomeGames) === 1 ) {
//            if( not 2vs2 than ) {
//                throw new \Exception('works only for 2vs2, implement for 1vs1 and 1vs2 ');
//            }
//
//
//
//            $minNrOfGamesToSwap = max($diffWithMax, $diffWithMin);
//            $minNrOfGamesToSwap = 3;
//        }
//
//
//
//        // bereken het aantal plekken die meer thuis hebben gespeeld
//        // zoek hier dan een wedstrijd voor
//        // als dit 1 is en het is 2vs2 dan zijn er minimaal 2 wedstrijden nodig
//
//
//        // als verschil onder en boven
//        disbalans koppelen aan (1vs1, 1vs2 en 2vs2) om te kijken hoeveel
//
//        // -- SITUATIE 1 : 15 15 15 16 14
//        // -- SITUATIE 2 : 15 16 14 16 14
//        // -- SITUATIE 3 : 15 15 14 17 14 DEZE KOMT MISSCHIEN NIET VOOR
//
//        kijk eerst hoeveel swaps je minimaal nodig bent om  (1vs1, 1vs2 en 2vs2) de balance weer gelijk
//
//        $homeCounterReport = $homeCounterMap->calculateReport();
//        $greater = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames + 1);
//        $greater = array_shift($greater);
//        if( $greater === null ) {
//            return null;
//        }
//        $equal = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames);
//        if( count($equal) === 0 ) {
//            return null;
//        }
//        $smaller = $homeCounterReport->getPlacesWithSameAmount($nrOfHomeGames - 1);
//        $smaller = array_shift($smaller);
//        if( $smaller === null ) {
//            return null;
//        }
//
//        // Filter all homeaways which have places which have more than average homeCounts
//        $greaterHomeHomeAways = $this->getHomeAwaysWithPlaceAtSide(Side::Home, $greater, $homeAways);
//        // Filter all homeaways which have places which have more than average homeCounts
//        $greaterNotInHome = $this->getHomeAwaysWithPlaceNotAtSide(Side::Home, $greater, $homeAways);
//        $greaterInAway = $this->getHomeAwaysWithPlaceNotAtSide(Side::Home, $greater, $homeAways);
//        $maxRouteLength = 10;
//        return $this->getSwapRouteHelper($greaterHomeHomeAways, $otherHomeAways, $smaller, [], $maxRouteLength);
//    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $otherHomeAways
     * @param int $targetPlaceNr
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $route
     * @param int $maxRouteLength
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>|null
     */
    protected function getSwapRouteHelper(
        array $homeAways,
        array $otherHomeAways,
        int $targetPlaceNr,
        array $route,
        int $maxRouteLength): array|null {

        if( count($homeAways) === 0 || count($otherHomeAways) === 0) {
            return null;
        }
        if( count($route) === $maxRouteLength) {
            return null;
        }

        foreach( $homeAways as $homeAway) {
            $routeToTry = $route;
            $routeToTry[] = $homeAway;
            if( $homeAway->hasPlaceNr($targetPlaceNr, Side::Away)) {
                return $routeToTry;
            }
            $newHomeHomeAways = $this->getHomeAwaysWithAPlaceAtSide(Side::Home, $homeAway->getAway(), $otherHomeAways);
            $newOtherHomeAways = $this->getHomeAwaysWithNotSomePlaceAtSide(Side::Home, $homeAway->getAway(), $otherHomeAways);

            $finalRoute = $this->getSwapRouteHelper($newHomeHomeAways, $newOtherHomeAways, $targetPlaceNr, $routeToTry, $maxRouteLength);
            if( $finalRoute !== null) {
                return $finalRoute;
            }
        }
        return null;
    }

//    /**
//     * @param int $nrOfHomeGames
//     * @param PlaceCombinationCounterMap $assignedHomeMap
//     * @return list<PlaceCombination>
//     */
//    protected function getWithNrOfHomeGames(int $nrOfHomeGames, PlaceCombinationCounterMap $assignedHomeMap): array {
//        $amountMap = $assignedHomeMap->getPerAmount();
//        if( !array_key_exists($nrOfHomeGames, $amountMap) ) {
//            return [];
//        }
//        return array_map( function(PlaceCombinationCounter $counter): PlaceCombination {
//            return $counter->getPlaceCombination();
//        }, $amountMap[$nrOfHomeGames]);
//    }

    /**
     *
     * BIJ VERGELIJKEN WELKE WEDSTRIJD HET BESTE IS:     *
     * TOTAAL HET MINST THUIS, GELIJK ? => DE MEESTE WEDSTRIJDEN HEEFT GESPEELD
     * @param SideNrCounterMap $assignedHomeNrCounterMap
     * @param SideNrCounterMap $assignedAwayNrCounterMap
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $sportHomeAwaysToAssign
     * @param bool $swapped
     * @return OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway|null
     */
    protected function getBestHomeAway(
        SideNrCounterMap $assignedHomeNrCounterMap,
        SideNrCounterMap $assignedAwayNrCounterMap,
        array $sportHomeAwaysToAssign,
        bool &$swapped): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway|null {
        $swapped = false;
        if( count($sportHomeAwaysToAssign) === 0) {
            return null;
        }
        $bestHomeAway = null;
        $lowestHomeCount = null;
        $highestCount = null;
        foreach( $sportHomeAwaysToAssign as $homeAway) {

            $homeCount = $this->sumPlaceNrsCount($assignedHomeNrCounterMap, $homeAway->convertToPlaceNrs(Side::Home));
            $highestHomeCountSinglePlace = $this->calculateHighestSinglePlaceCount($assignedHomeNrCounterMap, $homeAway->getHome());
            $count =  $homeCount + $this->sumPlaceNrsCount($assignedAwayNrCounterMap, $homeAway->getHome());

            if( $bestHomeAway === null || $homeCount < $lowestHomeCount) {
                $lowestHomeCount = $homeCount;
                $highestCount = $count;
                $bestHomeAway = $homeAway;
            } else if( $homeCount === $lowestHomeCount && $count > $highestCount) {
                $highestCount = $count;
                $bestHomeAway = $homeAway;
            }

            $swappedHomeAway = $homeAway->swap();
            $swappedHomeCount = $this->sumPlaceNrsCount($assignedHomeNrCounterMap, $swappedHomeAway->getHome());
            $swappedHighestHomeCountSinglePlace = $this->calculateHighestSinglePlaceCount($assignedHomeNrCounterMap, $swappedHomeAway->getHome());
            $swappedCount =  $swappedHomeCount + $this->sumPlaceNrsCount($assignedAwayNrCounterMap, $swappedHomeAway->getHome());

            if( $swappedHomeCount < $lowestHomeCount) {
                $lowestHomeCount = $swappedHomeCount;
                $highestCount = $swappedCount;
                $bestHomeAway = $homeAway;
                $swapped = true;
            } else if( $swappedHomeCount === $lowestHomeCount && $swappedCount > $highestCount) {
                $highestCount = $swappedHomeCount;
                $bestHomeAway = $homeAway;
                $swapped = true;
            } else if( $swappedHomeCount === $lowestHomeCount && $swappedCount === $highestCount
                && $highestHomeCountSinglePlace > $swappedHighestHomeCountSinglePlace) {
                $highestCount = $swappedHomeCount;
                $bestHomeAway = $homeAway;
                $swapped = true;
            }
        }
        return $bestHomeAway;
    }

    // BIJ VERGELIJKEN WELKE WEDSTRIJD HET BESTE IS:
//    private function shouldSwap(

    //  1 IN TOTAAL => HET LAAGSTE THUIS (VAN THUISPLEKKEN) + HET LAAGSTE THUIS (VAN UITPLEKKEN)
    // WANNEER GELIJK KIJK WIE DE MINSTE WEDSTRIJDEN HEEFT GESPEELD
    //      $homeCountHome = $this->sumPlaceNrsCount($homePlaceCounterMap, $homeAway->getHome());
//        $homeCountAway = $this->sumPlaceNrsCount($homePlaceCounterMap, $homeAway->getAway());
//        $awayCountHome = $this->sumPlaceNrsCount($awayPlaceCounterMap, $homeAway->getHome());
//        $awayCountAway = $this->sumPlaceNrsCount($awayPlaceCounterMap, $homeAway->getAway());
//        return ( $homeCountHome > $homeCountAway
//            || ($homeCountHome === $homeCountAway && $awayCountHome < $awayCountAway) );
//    }

    /**
     * @param SideNrCounterMap $sideNrCounterMap
     * @param list<int> $placeNrs
     * @return int
     */
    protected function sumPlaceNrsCount(SideNrCounterMap $sideNrCounterMap, array $placeNrs): int {
        return array_sum( array_map( function(int $placeNr) use ($sideNrCounterMap): int {
            return $sideNrCounterMap->count($placeNr);
        }, $placeNrs ) );
    }

//    protected function getLowestPlaceCount(PlaceCounterMap $placeCounterMap, PlaceCombination $placeCombination): int {
//
//        $lowestCount = 0;
//        foreach( $placeCombination->getPlaces() as $place) {
//            $count = $placeCounterMap->count($place);
//            if( $lowestCount === 0 || $count < $lowestCount ) {
//                $lowestCount = $count;
//            }
//        }
//        return $lowestCount;
//    }

    /**
     * @param SideNrCounterMap $sideNrCounterMap
     * @param list<int> $placeNrs
     * @return int
     */
    protected function calculateHighestSinglePlaceCount(SideNrCounterMap $sideNrCounterMap, array $placeNrs): int {

        $highestCount = 0;
        foreach( $placeNrs as $placeNr) {
            $count = $sideNrCounterMap->count($placeNr);
            if( $highestCount === 0 || $count > $highestCount ) {
                $highestCount = $count;
            }
        }
        return $highestCount;
    }

    private function getHomeDifference(SideNrCounterMap $homeNrCounterMap, OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): int {
        $homeDiff = $this->sumPlaceNrsCount($homeNrCounterMap, $homeAway->convertToPlaceNrs(Side::Home))
            - $this->sumPlaceNrsCount($homeNrCounterMap, $homeAway->convertToPlaceNrs(Side::Away));
        return max($homeDiff, 0);
    }

    /**
     * @param Side $side
     * @param int $placeNr
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getHomeAwaysWithPlaceAtSide(Side $side, int $placeNr, array $homeAways): array {
        return array_values( array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use($side, $placeNr): bool {
            return $homeAway->hasPlaceNr($placeNr, $side);
        }));
    }

    /**
     * @param Side $side
     * @param int $placeNr
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getHomeAwaysWithPlaceNotAtSide(Side $side, int $placeNr, array $homeAways): array {
        return array_values( array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use($side, $placeNr): bool {
            return !$homeAway->hasPlaceNr($placeNr, $side);
        }));
    }

    /**
     * @param Side $side
     * @param int|DuoPlaceNr $duoPlaceNr
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getHomeAwaysWithAPlaceAtSide(Side $side, int|DuoPlaceNr $duoPlaceNr, array $homeAways): array {
        $placeNrs = ($duoPlaceNr instanceof DuoPlaceNr) ? $duoPlaceNr->getPlaceNrs() : [$duoPlaceNr];
        return array_values( array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use($side, $placeNrs): bool {
            foreach( $placeNrs as $placeNr) {
                if( $homeAway->hasPlaceNr($placeNr, $side) ) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * @param Side $side
     * @param int|DuoPlaceNr $duoPlaceNr
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected function getHomeAwaysWithNotSomePlaceAtSide(Side $side, int|DuoPlaceNr $duoPlaceNr, array $homeAways): array {
        $placeNrs = ($duoPlaceNr instanceof DuoPlaceNr) ? $duoPlaceNr->getPlaceNrs() : [$duoPlaceNr];
        return array_values( array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use($side, $placeNrs): bool {
            foreach( $placeNrs as $placeNr) {
                if( $homeAway->hasPlaceNr($placeNr, $side) ) {
                    return false;
                }
            }
            return true;
        }));
    }

//    /**
//     * @param Side $side
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @return SideNrCounterMap
//     */
//    private function createSideNrCounterMap(Side $side, array $homeAways): SideNrCounterMap
//    {
//        $homeNrCounterMap =  new SideNrCounterMap($side);
//        $homeNrCounterMap->addHomeAways($homeAways);
//        return $homeNrCounterMap;
//    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param Side $side
     * @param list<int> $sidePlaceNrs
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function getHomeAwaysBySide(array $homeAways, Side $side, array $sidePlaceNrs): array
    {
        return array_values(array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use ($side, $placeNrs): bool{
            return $homeAway->getPlaces($side) == $places;
        }));
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param Side $side
     * @param int $placeNr
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function getHomeAwaysByPlaceNr(array $homeAways, Side $side, int $placeNr): array
    {
        return array_values(array_filter($homeAways, function(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) use ($side, $placeNr): bool{
            return $homeAway->hasPlaceNr($placeNr, $side);
        }));
    }
}