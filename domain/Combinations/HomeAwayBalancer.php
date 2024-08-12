<?php

namespace SportsScheduler\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\Amount\Range;
use SportsPlanning\Combinations\CombinationMapper;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwaySearcher;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideCounterMap;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Output\Combinations\HomeAwayOutput;
use SportsPlanning\Place;

class HomeAwayBalancer
{
    public function __construct(private LoggerInterface $logger)
    {
    }


    /**
     * @param SideCounterMap $homeCounterMapFromPreviousSports
     * @param SideCounterMap $awayCounterMapFromPreviousSports
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    public function balance2(
        SideCounterMap $homeCounterMapFromPreviousSports,
        Range $allowedHomeRange,
        SideCounterMap $awayCounterMapFromPreviousSports,
        array $sportHomeAways): array {
        $sportHomeAwaysAfterAdding = $this->addHomeAwaysToExisting(
            $homeCounterMapFromPreviousSports,
            $awayCounterMapFromPreviousSports,
            $sportHomeAways
        );

        // $sportHomeAwaysAfterAdding
        // Adding => Start
        $homeCounterMap = clone $homeCounterMapFromPreviousSports;
        $homeCounterMap->addHomeAways($sportHomeAwaysAfterAdding);
        $awayCounterMap = clone $awayCounterMapFromPreviousSports;
        $awayCounterMap->addHomeAways($sportHomeAwaysAfterAdding);
        $rangedHomeCounterMap = new RangedPlaceCounterMap($homeCounterMap, $allowedHomeRange );
        if ( $rangedHomeCounterMap->withinRange(0) ) {
            return $sportHomeAwaysAfterAdding;
        }

        $homeCounterReport = $rangedHomeCounterMap->calculateReport();

        $homeCounterMap->output($this->logger, '', 'ha after swapping');
        (new HomeAwayOutput())->outputHomeAways($sportHomeAwaysAfterAdding,'ha after adding');

        if( $homeCounterReport->getTotalAboveMaximum() === 1 && $homeCounterReport->getTotalBelowMinimum() === 1 ) {
            $homeAwaysToSwap = $this->getHomeAwaysToSwapOneTooManyOneTooFew(
                $rangedHomeCounterMap, $sportHomeAwaysAfterAdding
            );
            $correctHomeAways = $sportHomeAwaysAfterAdding;
            $this->swapHomeAways($homeCounterMap, $awayCounterMap, $correctHomeAways, $homeAwaysToSwap);

//            $homeCounterMap = $this->createSideCounterMap(Side::Home, $correctHomeAways);


            $rangedHomeCounterMap = new RangedPlaceCounterMap($homeCounterMap, $allowedHomeRange );
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
     * @param SideCounterMap $homeCounterMapFromPreviousSports
     * @param SideCounterMap $awayCounterMapFromPreviousSports
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function addHomeAwaysToExisting(
        SideCounterMap $homeCounterMapFromPreviousSports,
        SideCounterMap $awayCounterMapFromPreviousSports,
        array $sportHomeAways): array {

        $newSportHomeAways = [];

        $homeCounterMap = clone $homeCounterMapFromPreviousSports;
        $awayCounterMap = clone $awayCounterMapFromPreviousSports;

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
     * @param RangedPlaceCounterMap $rangedHomeCounterMap
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     * @throws \Exception
     */
    private function getHomeAwaysToSwapOneTooManyOneTooFew(
        RangedPlaceCounterMap $rangedHomeCounterMap, array $homeAways): array {
        $placesOneTimeTooManyHome = $rangedHomeCounterMap->getPlacesAboveMaximum();
        $placeOneTimeTooManyHome = reset($placesOneTimeTooManyHome);
        if( count($placesOneTimeTooManyHome) !== 1 || $placeOneTimeTooManyHome === false) {
            throw new \Exception('there should be 1 place above the maximum');
        }
        $placesOneTimeTooFewHome = $rangedHomeCounterMap->getPlacesBelowMinimum();
        $placeOneTimeTooFewHome = reset($placesOneTimeTooFewHome);
        if( count($placesOneTimeTooFewHome) !== 1 || $placeOneTimeTooFewHome === false ) {
            throw new \Exception('there should be 1 place above the maximum');
        }

        $awayPlaces = [$placeOneTimeTooManyHome, $placeOneTimeTooFewHome];
        $swappableHomeAways = (new HomeAwaySearcher())->getHomeAwaysBySide($homeAways, Side::Away, $awayPlaces);
        foreach( $swappableHomeAways as $swappableHomeAway) {
            $swappableHomeAwaysStepTwo = $this->getHomeAwaysToSwapOneTooManyOneTooFewStepTwo(
                $rangedHomeCounterMap, $homeAways, $swappableHomeAway->getPlaces(Side::Home), $placeOneTimeTooManyHome
            );
            if( $swappableHomeAwaysStepTwo === null ) {
                continue;
            }
            return array_merge([$swappableHomeAway], $swappableHomeAwaysStepTwo);
        }
        throw new \Exception('other swappables should be found');
    }

    /**
     * @param RangedPlaceCounterMap $rangedHomeCounterMap
     * @param list<HomeAway> $homeAways
     * @param list<Place> $previousHomePlaces
     * @param Place $placeOneTimeTooManyHome
     * @return list<HomeAway>|null
     * @throws \Exception
     */
    private function getHomeAwaysToSwapOneTooManyOneTooFewStepTwo(
        RangedPlaceCounterMap $rangedHomeCounterMap, array $homeAways,
        array $previousHomePlaces, Place $placeOneTimeTooManyHome): array|null {

        $homeAwaysWithTooManyHome = (new HomeAwaySearcher())->getHomeAwaysByPlace($homeAways, Side::Home, $placeOneTimeTooManyHome);
        $firstPreviousHomePlace = $previousHomePlaces[0];
        $homeAwaysFirstPreviousHomePlace = (new HomeAwaySearcher())->getHomeAwaysByPlace($homeAwaysWithTooManyHome, Side::Away, $firstPreviousHomePlace);
        $secondPreviousHomePlace = $previousHomePlaces[1];
        $homeAwaysSecondPreviousHomePlace = (new HomeAwaySearcher())->getHomeAwaysByPlace($homeAwaysWithTooManyHome, Side::Away, $secondPreviousHomePlace);
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
     * @param PlaceCounterMap $homeCounterMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function getHomeAwaysWithAtLeastTwoDifference(PlaceCounterMap $homeCounterMap, array $sportHomeAways): array {
        $filteredHomeAway = array_filter( $sportHomeAways, function(HomeAway $homeAway) use ($homeCounterMap): bool {
            return $this->getHomeDifference($homeCounterMap, $homeAway) > 1;
        });
        uasort($filteredHomeAway, function (HomeAway $homeAwayA,HomeAway $homeAwayB) use ($homeCounterMap): int {
            return $this->getHomeDifference($homeCounterMap, $homeAwayA)
                - $this->getHomeDifference($homeCounterMap, $homeAwayB);
        });
        // $logger->info("sorted homeaways in " . (microtime(true) - $time_start));
        // (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
        return array_values($filteredHomeAway);
    }

    /**
     * @param PlaceCounterMap $homePlaceCounterMap
     * @param PlaceCounterMap $awayPlaceCounterMap
     * @param list<HomeAway> $sportHomeAways
     * @param list<HomeAway> $homeAwaysToSwap
     * @return void
     */
    protected function swapHomeAways(
        PlaceCounterMap $homePlaceCounterMap,
        PlaceCounterMap $awayPlaceCounterMap,
        array &$sportHomeAways, array $homeAwaysToSwap): void {

        foreach( $homeAwaysToSwap as $homeAwayToSwap) {
            $key = array_search($homeAwayToSwap, $sportHomeAways, true);
            if( $key === false ) {
                continue;
            }
            array_splice($sportHomeAways, $key, 1);
            foreach( $homeAwayToSwap->getHome()->getPlaces() as $homePlace) {
                $homePlaceCounterMap->removePlace($homePlace);
            }
            foreach( $homeAwayToSwap->getAway()->getPlaces() as $awayPlace) {
                $awayPlaceCounterMap->removePlace($awayPlace);
            }
            $swappedHomeAway = $homeAwayToSwap->swap();
            foreach( $swappedHomeAway->getHome()->getPlaces() as $homePlace) {
                $homePlaceCounterMap->addPlace($homePlace);
            }
            foreach( $swappedHomeAway->getAway()->getPlaces() as $awayPlace) {
                $awayPlaceCounterMap->addPlace($awayPlace);
            }
            $sportHomeAways[] = $swappedHomeAway;
        }
    }

    /**
     * @param list<HomeAway> $sportHomeAways
     * @param list<HomeAway> $newHomeAways
     * @return list<HomeAway>
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
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getSwappedHomeAways(array $homeAways): array {
        return array_map(function(HomeAway $homeAway): HomeAway {
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
     * @param list<HomeAway> $homeAways
     * @param list<HomeAway> $otherHomeAways
     * @param Place $targetPlace
     * @param list<HomeAway> $route
     * @param int $maxRouteLength
     * @return list<HomeAway>|null
     */
    protected function getSwapRouteHelper(
        array $homeAways,
        array $otherHomeAways,
        Place $targetPlace,
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
            if( $homeAway->hasPlace($targetPlace, Side::Away)) {
                return $routeToTry;
            }
            $newHomeHomeAways = $this->getHomeAwaysWithAPlaceAtSide(Side::Home, $homeAway->getAway(), $otherHomeAways);
            $newOtherHomeAways = $this->getHomeAwaysWithNotAPlaceAtSide(Side::Home, $homeAway->getAway(), $otherHomeAways);

            $finalRoute = $this->getSwapRouteHelper($newHomeHomeAways, $newOtherHomeAways, $targetPlace, $routeToTry, $maxRouteLength);
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
     * @param SideCounterMap $assignedHomeCounterMap
     * @param SideCounterMap $assignedAwayCounterMap
     * @param list<HomeAway> $sportHomeAwaysToAssign
     * @param bool $swapped
     * @return HomeAway|null
     */
    protected function getBestHomeAway(
        PlaceCounterMap $assignedHomeCounterMap,
        PlaceCounterMap $assignedAwayCounterMap,
        array $sportHomeAwaysToAssign,
        bool &$swapped): HomeAway|null {
        $swapped = false;
        if( count($sportHomeAwaysToAssign) === 0) {
            return null;
        }
        $bestHomeAway = null;
        $lowestHomeCount = null;
        $highestCount = null;
        foreach( $sportHomeAwaysToAssign as $homeAway) {

            $homeCount = $this->countPlaceCombination($assignedHomeCounterMap, $homeAway->getHome());
            $highestHomeCountSinglePlace = $this->calculateHighestSinglePlaceCount($assignedHomeCounterMap, $homeAway->getHome());
            $count =  $homeCount + $this->countPlaceCombination($assignedAwayCounterMap, $homeAway->getHome());

            if( $bestHomeAway === null || $homeCount < $lowestHomeCount) {
                $lowestHomeCount = $homeCount;
                $highestCount = $count;
                $bestHomeAway = $homeAway;
            } else if( $homeCount === $lowestHomeCount && $count > $highestCount) {
                $highestCount = $count;
                $bestHomeAway = $homeAway;
            }

            $swappedHomeAway = $homeAway->swap();
            $swappedHomeCount = $this->countPlaceCombination($assignedHomeCounterMap, $swappedHomeAway->getHome());
            $swappedHighestHomeCountSinglePlace = $this->calculateHighestSinglePlaceCount($assignedHomeCounterMap, $swappedHomeAway->getHome());
            $swappedCount =  $swappedHomeCount + $this->countPlaceCombination($assignedAwayCounterMap, $swappedHomeAway->getHome());

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
    //      $homeCountHome = $this->countPlaceCombination($homePlaceCounterMap, $homeAway->getHome());
//        $homeCountAway = $this->countPlaceCombination($homePlaceCounterMap, $homeAway->getAway());
//        $awayCountHome = $this->countPlaceCombination($awayPlaceCounterMap, $homeAway->getHome());
//        $awayCountAway = $this->countPlaceCombination($awayPlaceCounterMap, $homeAway->getAway());
//        return ( $homeCountHome > $homeCountAway
//            || ($homeCountHome === $homeCountAway && $awayCountHome < $awayCountAway) );
//    }

    protected function countPlaceCombination(PlaceCounterMap $placeCounterMap, PlaceCombination $placeCombination): int {
        $count = 0;
        foreach( $placeCombination->getPlaces() as $place) {
            $count += $placeCounterMap->count($place);
        }
        return $count;
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

    protected function calculateHighestSinglePlaceCount(PlaceCounterMap $placeCounterMap, PlaceCombination $placeCombination): int {

        $highestCount = 0;
        foreach( $placeCombination->getPlaces() as $place) {
            $count = $placeCounterMap->count($place);
            if( $highestCount === 0 || $count > $highestCount ) {
                $highestCount = $count;
            }
        }
        return $highestCount;
    }

    private function getHomeDifference(PlaceCounterMap $homePlaceCounterMap, HomeAway $sportHomeAway): int {
        $homeDiff = $this->countPlaceCombination($homePlaceCounterMap, $sportHomeAway->getHome())
            - $this->countPlaceCombination($homePlaceCounterMap, $sportHomeAway->getAway());
        return max($homeDiff, 0);
    }

    /**
     * @param Side $side
     * @param Place $place
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithPlaceAtSide(Side $side, Place $place, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $place): bool {
            return $homeAway->hasPlace($place, $side);
        }));
    }

    /**
     * @param Side $side
     * @param Place $place
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithPlaceNotAtSide(Side $side, Place $place, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $place): bool {
            return !$homeAway->hasPlace($place, $side);
        }));
    }

    /**
     * @param Side $side
     * @param PlaceCombination $placeCombination
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithAPlaceAtSide(Side $side, PlaceCombination $placeCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeCombination): bool {
            foreach( $placeCombination->getPlaces() as $place) {
                if( $homeAway->hasPlace($place, $side) ) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * @param Side $side
     * @param PlaceCombination $placeCombination
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithNotAPlaceAtSide(Side $side, PlaceCombination $placeCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeCombination): bool {
            foreach( $placeCombination->getPlaces() as $place) {
                if( $homeAway->hasPlace($place, $side) ) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * @param Side $side
     * @param list<HomeAway> $homeAways
     * @return SideCounterMap
     */
    private function createSideCounterMap(Side $side, array $homeAways): SideCounterMap
    {
        $combinationMapper = new CombinationMapper();
        $placeCounterMap = $combinationMapper->initPlaceCounterMapForHomeAways($homeAways);
        $homeCounterMap =  new SideCounterMap($side, $placeCounterMap);
        foreach( $homeAways as $homeAway ) {
            $homeCounterMap->addHomeAway($homeAway);
        }
        return $homeCounterMap;
    }
}