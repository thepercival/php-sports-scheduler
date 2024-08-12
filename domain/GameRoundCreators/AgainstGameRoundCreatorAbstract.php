<?php

declare(strict_types=1);

namespace SportsScheduler\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Output\Combinations\GameRoundOutput;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Poule;

abstract class AgainstGameRoundCreatorAbstract
{
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param list<HomeAway> $homeAways
     * @return AgainstGameRound
     */
    protected function toNextGameRound(AgainstGameRound $gameRound, array &$homeAways): AgainstGameRound
    {
        foreach ($gameRound->getHomeAways() as $homeAway) {
            $foundHomeAwayIndex = array_search($homeAway, $homeAways, true);
            if ($foundHomeAwayIndex !== false) {
                array_splice($homeAways, $foundHomeAwayIndex, 1);
            }
        }
        return $gameRound->createNext();
    }

    protected function isGameRoundCompleted(AgainstH2hWithPoule|AgainstGppWithPoule $variantWithPoule, AgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $variantWithPoule->getNrOfGamesSimultaneously();
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param HomeAway $homeAway
     * @param array<int, CounterForPlace> $assignedSportMap
     * @param array<int, CounterForPlace> $assignedMap
     * @param array<string, CounterForPlaceCombination> $assignedWithMap
     * @param array<string, CounterForPlaceCombination> $assignedAgainstMap
     * @param array<int, CounterForPlace> $assignedHomeMap
     */
    protected function assignHomeAway(
        AgainstGameRound $gameRound,
        HomeAway         $homeAway,
        array            &$assignedSportMap,
        array            &$assignedMap,
        array            &$assignedWithMap,
        array            &$assignedAgainstMap,
        array            &$assignedHomeMap
    ): void {
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportCounter = $assignedSportMap[$place->getPlaceNr()];
            $assignedSportMap[$place->getPlaceNr()] = $assignedSportCounter->increment();
            $assignedCounter = $assignedMap[$place->getPlaceNr()];
            $assignedMap[$place->getPlaceNr()] = $assignedCounter->increment();
        }
        $assignedWithHomeCounter = $assignedWithMap[$homeAway->getHome()->getIndex()];
        $assignedWithMap[$homeAway->getHome()->getIndex()] = $assignedWithHomeCounter ->increment();
        $assignedWithAwayCounter = $assignedWithMap[$homeAway->getAway()->getIndex()];
        $assignedWithMap[$homeAway->getAway()->getIndex()] = $assignedWithAwayCounter->increment();
        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
            $assignedAgainstCounter = $assignedAgainstMap[$againstPlaceCombination->getIndex()];
            $assignedAgainstMap[$againstPlaceCombination->getIndex()] = $assignedAgainstCounter->increment();
        }

        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeCounter = $assignedHomeMap[$homePlace->getPlaceNr()];
            $assignedHomeMap[$homePlace->getPlaceNr()] = $assignedHomeCounter->increment();
        }
        $gameRound->add($homeAway);
    }

    protected function releaseHomeAway(AgainstGameRound $gameRound, HomeAway $homeAway): void
    {
        $gameRound->remove($homeAway);
    }

//    /**
//     * @param AgainstHomeAway $homeAway
//     * @param array<int, PlaceCounter> $assignedSportMap
//     * @return bool
//     */
//    private function willBeTooMuchAssignedDiff(AgainstHomeAway $homeAway, array $assignedSportMap): bool
//    {
//        $diff = 2;
//
//        foreach ($homeAway->getPlaces() as $place) {
//            $minOfGames = $assignedSportMap[$place->getNumber()]->count() - $diff;
//            foreach( $assignedSportMap as $assignedCounter ) {
//                if( $assignedCounter->getPlace() === $place) {
//                    continue;
//                }
//                if( $assignedCounter->count() < $minOfGames ) {
//                    // if in same game and only 1 outOfBounds than still continue
//                    if( $homeAway->hasPlace($assignedCounter->getPlace())
//                        && $assignedCounter->count() === ($minOfGames - 1 )
//                    ) {
//                        continue;
//                    }
//                    return true;
//                }
//            }
//
//        }
//        return false;
//    }


    /**
     * @param Poule $poule
     * @return array<int, CounterForPlace>
     */
    protected function getAssignedSportCounters(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            $map[$place->getPlaceNr()] = new CounterForPlace($place);
        }
        return $map;
    }

    /**
     * @param list<HomeAway> $homeAways
     */
    protected function outputUnassignedHomeAways(array $homeAways): void
    {
        $this->logger->info('unassigned');
        foreach ($homeAways as $homeAway) {
            $this->logger->info($homeAway);
        }
    }

    /**
     * @param list<HomeAway> $homeAways
     */
    protected function outputUnassignedTotals(array $homeAways): void
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                if (!isset($map[(string)$place])) {
                    $map[(string)$place] = new CounterForPlace($place);
                }
                $tmpPlaceCounter = $map[(string)$place];
                $map[(string)$place] = $tmpPlaceCounter->increment();
            }
        }
        foreach ($map as $location => $placeCounter) {
            $this->logger->info($location . ' => ' . $placeCounter->count());
        }
    }

    /**
     * @param array<string, CounterForPlaceCombination> $map
     * @return array<string, PlaceCombination>
     */
    protected function convertToPlaceCombinationMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = $counter->getPlaceCombination();
        }
        return $newMap;
    }
}
