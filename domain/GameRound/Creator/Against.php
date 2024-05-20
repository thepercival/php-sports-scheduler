<?php

declare(strict_types=1);

namespace SportsScheduler\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Output\Combinations\GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

abstract class Against
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
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<string, PlaceCombinationCounter> $assignedWithMap
     * @param array<string, PlaceCombinationCounter> $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
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
            $assignedSportMap[$place->getPlaceNr()]->increment();
            $assignedMap[$place->getPlaceNr()]->increment();
        }
        $assignedWithMap[$homeAway->getHome()->getIndex()]->increment();
        $assignedWithMap[$homeAway->getAway()->getIndex()]->increment();
        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
            $assignedAgainstMap[$againstPlaceCombination->getIndex()]->increment();
        }

        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getPlaceNr()]->increment();
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
     * @return array<int, PlaceCounter>
     */
    protected function getAssignedSportCounters(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            $map[$place->getPlaceNr()] = new PlaceCounter($place);
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
                    $map[(string)$place] = new PlaceCounter($place);
                }
                $map[(string)$place]->increment();
            }
        }
        foreach ($map as $location => $placeCounter) {
            $this->logger->info($location . ' => ' . $placeCounter->count());
        }
    }

    /**
     * @param array<string, PlaceCombinationCounter> $map
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
