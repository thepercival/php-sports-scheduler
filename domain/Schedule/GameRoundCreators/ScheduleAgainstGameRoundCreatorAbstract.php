<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\GameRoundCreators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCombinationCounter;
use SportsPlanning\Combinations\PlaceNrCounter;
use SportsPlanning\Output\Combinations\ScheduleGameRoundOutput;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\GameRounds\ScheduleAgainstGameRound;
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;
use SportsPlanning\SportVariant\AgainstH2hWithNrOfPlaces;

abstract class ScheduleAgainstGameRoundCreatorAbstract
{
    protected ScheduleGameRoundOutput $scheduleGameRoundOutput;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->scheduleGameRoundOutput = new ScheduleGameRoundOutput($logger);
    }

    /**
     * @param ScheduleAgainstGameRound $gameRound
     * @param list<HomeAway> $homeAways
     * @return ScheduleAgainstGameRound
     */
    protected function toNextGameRound(ScheduleAgainstGameRound $gameRound, array &$homeAways): ScheduleAgainstGameRound
    {
        foreach ($gameRound->getHomeAways() as $homeAway) {
            $foundHomeAwayIndex = array_search($homeAway, $homeAways, true);
            if ($foundHomeAwayIndex !== false) {
                array_splice($homeAways, $foundHomeAwayIndex, 1);
            }
        }
        return $gameRound->createNext();
    }

    protected function isGameRoundCompleted(
        AgainstH2hWithNrOfPlaces|AgainstGppWithNrOfPlaces $variantWithNrOfPlaces,
        ScheduleAgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $variantWithNrOfPlaces->getNrOfGamesSimultaneously();
    }

    /**
     * @param ScheduleAgainstGameRound $gameRound
     * @param HomeAway $homeAway
     * @param array<int, PlaceNrCounter> $assignedSportMap
     * @param array<int, PlaceNrCounter> $assignedMap
     * @param array<string, PlaceNrCombinationCounter> $assignedWithMap
     * @param array<string, PlaceNrCombinationCounter> $assignedAgainstMap
     * @param array<int, PlaceNrCounter> $assignedHomeMap
     */
    protected function assignHomeAway(
        ScheduleAgainstGameRound $gameRound,
        HomeAway         $homeAway,
        array            &$assignedSportMap,
        array            &$assignedMap,
        array            &$assignedWithMap,
        array            &$assignedAgainstMap,
        array            &$assignedHomeMap
    ): void {
        foreach ($homeAway->getPlaceNrs() as $placeNr) {
            $assignedSportMap[$placeNr]->increment();
            $assignedMap[$placeNr]->increment();
        }
        $assignedWithMap[$homeAway->getHome()->getIndex()]->increment();
        $assignedWithMap[$homeAway->getAway()->getIndex()]->increment();
        foreach($homeAway->getAgainstPlaceNrCombinations() as $againstPlaceNrCombination) {
            $assignedAgainstMap[$againstPlaceNrCombination->getIndex()]->increment();
        }

        foreach ($homeAway->getHome()->getPlaceNrs() as $homePlaceNr) {
            $assignedHomeMap[$homePlaceNr]->increment();
        }
        $gameRound->add($homeAway);
    }

    protected function releaseHomeAway(ScheduleAgainstGameRound $gameRound, HomeAway $homeAway): void
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
     * @return array<int, PlaceNrCounter>
     */
    protected function getAssignedSportCounters(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaceNrs() as $placeNr) {
            $map[$placeNr] = new PlaceNrCounter($placeNr);
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

//    /**
//     * @param list<HomeAway> $homeAways
//     */
//    protected function outputUnassignedTotals(array $homeAways): void
//    {
//        $map = [];
//        foreach ($homeAways as $homeAway) {
//            foreach ($homeAway->getPlaceNrs() as $placeNr) {
//                if (!isset($map[(string)$place])) {
//                    $map[(string)$place] = new PlaceNrCounter($placeNr);
//                }
//                $map[(string)$place]->increment();
//            }
//        }
//        foreach ($map as $location => $placeCounter) {
//            $this->logger->info($location . ' => ' . $placeCounter->count());
//        }
//    }

    /**
     * @param array<string, PlaceNrCombinationCounter> $map
     * @return array<string, PlaceNrCombination>
     */
    protected function convertToPlaceNrCombinationMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = $counter->getPlaceNrCombination();
        }
        return $newMap;
    }
}
