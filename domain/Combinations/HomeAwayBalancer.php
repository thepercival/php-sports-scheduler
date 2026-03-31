<?php

namespace SportsScheduler\Combinations;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCombinationCounter;
use SportsPlanning\Combinations\PlaceNrCombinationCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;

final class HomeAwayBalancer
{
    public function __construct()
    {
    }


    /**
     * @param RangedPlaceNrCombinationCounterMap $assignedHomeBaseMap
     * @param PlaceNrCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    public function balance2(
        RangedPlaceNrCombinationCounterMap $assignedHomeBaseMap,
        PlaceNrCombinationCounterMap $assignedAwayMap,
        array $sportHomeAways): array {

        $assignedHomeMap = $assignedHomeBaseMap->getMap();
        $sportHomeAwaysAfterAdding = $this->addHomeAwaysToExisting(
            $assignedHomeMap,
            $assignedAwayMap,
            $sportHomeAways
        );


        $rangedAssignedHomeBaseMap = new RangedPlaceNrCombinationCounterMap(
            $assignedHomeMap, $assignedHomeBaseMap->getAllowedRange()
        );
        if( $rangedAssignedHomeBaseMap->withinRange(0) ) {
            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
        }

        $sportHomeAwaysAfterMajorBalancing = $sportHomeAwaysAfterAdding;
        $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($assignedHomeMap, $sportHomeAwaysAfterMajorBalancing);
        while ( count($homeAwaysToSwap) > 0) {
            $this->swapHomeAways(
                $assignedHomeMap,
                $assignedAwayMap,
                $sportHomeAwaysAfterMajorBalancing,
                $homeAwaysToSwap);
            $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($assignedHomeMap, $sportHomeAwaysAfterMajorBalancing);
        }
        if( $rangedAssignedHomeBaseMap->withinRange(0)
            || $rangedAssignedHomeBaseMap->getAllowedRange()->getAmountDifference() > 0 ) {
            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
        }

        // $assignedHomeMap->output($this->logger, '', 'before minor');

        $nrOfHomeGames = $rangedAssignedHomeBaseMap->getAllowedRange()->getMax()->amount;
        $sportHomeAwaysAfterMinorBalancing = $sportHomeAwaysAfterMajorBalancing;
        $swapRoute = $this->getSwapRoute($nrOfHomeGames, $assignedHomeMap, $assignedAwayMap, $sportHomeAwaysAfterMinorBalancing);
        while ( $swapRoute !== null ) {
            $this->swapHomeAways(
                $assignedHomeMap,
                $assignedAwayMap,
                $sportHomeAwaysAfterMinorBalancing,
                $swapRoute);
            $swapRoute = $this->getSwapRoute($nrOfHomeGames, $assignedHomeMap, $assignedAwayMap, $sportHomeAwaysAfterMinorBalancing);
        }
        // $assignedHomeMap->output($this->logger, '', 'after minor');
        return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterMinorBalancing );
    }

    /**
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @param PlaceNrCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function addHomeAwaysToExisting(
        PlaceNrCombinationCounterMap &$assignedHomeMap,
        PlaceNrCombinationCounterMap &$assignedAwayMap,
        array $sportHomeAways): array {

        $newSportHomeAways = [];
        while( $sportHomeAway = $this->getBestSwappableHomeAway( $assignedHomeMap, $assignedAwayMap, $sportHomeAways ) ) {
            $key = array_search($sportHomeAway, $sportHomeAways, true);
            if( $key !== false ) {
                array_splice($sportHomeAways, $key, 1);
            }
            if( $this->shouldSwap($assignedHomeMap, $assignedAwayMap, $sportHomeAway) ) {
                $sportHomeAway = $sportHomeAway->swap();
            }
            $newSportHomeAways[] = $sportHomeAway;
            $assignedHomeMap = $assignedHomeMap->addPlaceNrCombination($sportHomeAway->getHome());
            $assignedAwayMap = $assignedAwayMap->addPlaceNrCombination($sportHomeAway->getAway());
        }
        return $newSportHomeAways;
    }

    /**
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function getHomeAwaysWithAtLeastTwoDifference(
        PlaceNrCombinationCounterMap $assignedHomeMap, array $sportHomeAways): array {
            return array_values(array_filter( $sportHomeAways, function(HomeAway $homeAway) use ($assignedHomeMap): bool {
                return $this->getHomeDifference($assignedHomeMap, $homeAway) > 1;
            }));
    }

    /**
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @param list<HomeAway> $homeAwaysToSwap
     * @return void
     */
    protected function swapHomeAways(
        PlaceNrCombinationCounterMap &$assignedHomeMap,
        PlaceNrCombinationCounterMap &$assignedAwayMap,
        array &$sportHomeAways, array $homeAwaysToSwap): void {
        foreach( $homeAwaysToSwap as $homeAwayToSwap) {
            $key = array_search($homeAwayToSwap, $sportHomeAways, true);
            if( $key === false ) {
                continue;
            }
            array_splice($sportHomeAways, $key, 1);
            $assignedHomeMap = $assignedHomeMap->removePlaceNrCombination($homeAwayToSwap->getHome());
            $assignedAwayMap = $assignedAwayMap->removePlaceNrCombination($homeAwayToSwap->getAway());
            $swappedHomeAway = $homeAwayToSwap->swap();
            $assignedHomeMap = $assignedHomeMap->addPlaceNrCombination($swappedHomeAway->getHome());
            $assignedAwayMap = $assignedAwayMap->addPlaceNrCombination($swappedHomeAway->getAway());
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
                array_push($sportHomeAways, $sportHomeAway);
                $sportHomeAway = array_shift($sportHomeAways);
            }
        }
        return $swappedHomeAways;
    }

    /**
     * @param int $nrOfHomeGames
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @param PlaceNrCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>|null
     */
    protected function getSwapRoute(
        int $nrOfHomeGames,
        PlaceNrCombinationCounterMap $assignedHomeMap,
        PlaceNrCombinationCounterMap $assignedAwayMap,
        array $homeAways): array|null {

        $greater = $this->getWithNrOfHomeGames($nrOfHomeGames + 1, $assignedHomeMap);
        $greater = array_shift($greater);
        if( $greater === null ) {
            return null;
        }
        $equal = $this->getWithNrOfHomeGames($nrOfHomeGames, $assignedHomeMap);
        if( count($equal) === 0 ) {
            return null;
        }
        $smaller = $this->getWithNrOfHomeGames($nrOfHomeGames - 1, $assignedHomeMap);
        $smaller = array_shift($smaller);
        if( $smaller === null ) {
            return null;
        }

        $greaterHomeHomeAways = $this->getHomeAwaysWithSide(AgainstSide::Home, $greater, $homeAways);
        $otherHomeAways = $this->getHomeAwaysNotWithSide(AgainstSide::Home, $greater, $homeAways);
        $maxRouteLength = 10;
        return $this->getSwapRouteHelper($greaterHomeHomeAways, $otherHomeAways, $smaller, [], $maxRouteLength);
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param list<HomeAway> $otherHomeAways
     * @param PlaceNrCombination $target
     * @param list<HomeAway> $route
     * @param int $maxRouteLength
     * @return list<HomeAway>|null
     */
    protected function getSwapRouteHelper(
        array $homeAways,
        array $otherHomeAways,
        PlaceNrCombination $target,
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
            if( $homeAway->getAway()->getIndex() === $target->getIndex()) {
                return $routeToTry;
            }
            $newHomeHomeAways = $this->getHomeAwaysWithSide(AgainstSide::Home, $homeAway->getAway(), $otherHomeAways);
            $newOtherHomeAways = $this->getHomeAwaysNotWithSide(AgainstSide::Home, $homeAway->getAway(), $otherHomeAways);

            $finalRoute = $this->getSwapRouteHelper($newHomeHomeAways, $newOtherHomeAways, $target, $routeToTry, $maxRouteLength);
            if( $finalRoute !== null) {
                return $finalRoute;
            }
        }
        return null;
    }



    /**
     * @param int $nrOfHomeGames
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @return list<PlaceNrCombination>
     */
    protected function getWithNrOfHomeGames(int $nrOfHomeGames, PlaceNrCombinationCounterMap $assignedHomeMap): array {
        $amountMap = $assignedHomeMap->getPerAmount();
        if( !array_key_exists($nrOfHomeGames, $amountMap) ) {
            return [];
        }
        return array_map( function(PlaceNrCombinationCounter $counter): PlaceNrCombination {
           return $counter->getPlaceNrCombination();
        }, $amountMap[$nrOfHomeGames]);
    }

    /**
     * @param PlaceNrCombinationCounterMap $assignedHomeMap
     * @param PlaceNrCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return HomeAway|null
     */
    protected function getBestSwappableHomeAway(
        PlaceNrCombinationCounterMap $assignedHomeMap,
        PlaceNrCombinationCounterMap $assignedAwayMap,
        array $sportHomeAways): HomeAway|null {

        if( count($sportHomeAways) === 0) {
            return null;
        }
        $bestHomeAway = null;
        $leastHome = null;
        foreach( $sportHomeAways as $homeAway) {
            if( $bestHomeAway === null || $assignedHomeMap->count($homeAway->getHome()) < $leastHome) {
                $leastHome = $assignedHomeMap->count($homeAway->getHome());
                $bestHomeAway = $homeAway;
            } else if( $leastHome === $assignedHomeMap->count($homeAway->getHome())
                && $assignedAwayMap->count($homeAway->getAway()) > $assignedAwayMap->count($bestHomeAway->getAway()) ) {
                $bestHomeAway = $homeAway;
            }
        }
        return $bestHomeAway;
    }

    private function shouldSwap(
        PlaceNrCombinationCounterMap $assignedHomeMap,
        PlaceNrCombinationCounterMap $assignedAwayMap,
        HomeAway $homeAway): bool {
        $homeCountHome = $assignedHomeMap->count($homeAway->getHome());
        $homeCountAway = $assignedHomeMap->count($homeAway->getAway());
        $awayCountHome = $assignedAwayMap->count($homeAway->getHome());
        $awayCountAway = $assignedAwayMap->count($homeAway->getAway());
        return ( $homeCountHome > $homeCountAway
            || ($homeCountHome === $homeCountAway && $awayCountHome < $awayCountAway) );
    }

    private function getHomeDifference(PlaceNrCombinationCounterMap $assignedHomeMap, HomeAway $sportHomeAway): int {
        $homeDiff = $assignedHomeMap->count($sportHomeAway->getHome())
            - $assignedHomeMap->count($sportHomeAway->getAway());
        return $homeDiff < 0 ? 0 : $homeDiff;
    }

    /**
     * @param AgainstSide $side
     * @param PlaceNrCombination $placeNrCombination
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithSide(AgainstSide $side, PlaceNrCombination $placeNrCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeNrCombination): bool {
            return $homeAway->get($side)->getIndex() === $placeNrCombination->getIndex();
        }));
    }

    /**
     * @param AgainstSide $side
     * @param PlaceNrCombination $placeNrCombination
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysNotWithSide(AgainstSide $side, PlaceNrCombination $placeNrCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeNrCombination): bool {
            return $homeAway->get($side)->getIndex() !== $placeNrCombination->getIndex();
        }));
    }

}