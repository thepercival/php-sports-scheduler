<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules\CycleCreators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Output\Combinations\GameRoundOutput;

abstract class CycleCreatorAgainstAbstract
{
//    protected GameRoundOutput $gameRoundOutput;

    public function __construct(protected LoggerInterface $logger)
    {
//        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

//    /**
//     * @param AgainstGameRound $gameRound
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     * @return AgainstGameRound
//     */
//    protected function toNextGameRound(AgainstGameRound $gameRound, array &$homeAways): AgainstGameRound
//    {
//        foreach ($gameRound->getHomeAways() as $homeAway) {
//            $foundHomeAwayIndex = array_search($homeAway, $homeAways, true);
//            if ($foundHomeAwayIndex !== false) {
//                array_splice($homeAways, $foundHomeAwayIndex, 1);
//            }
//        }
//        return $gameRound->createNext();
//    }
//
//    protected function isGameRoundCompleted(AgainstH2hWithNrOfPlaces|AgainstGppWithNrOfPlaces $againstWithNrOfPlaces, AgainstGameRound $gameRound): bool
//    {
//        return count($gameRound->getHomeAways()) === $againstWithNrOfPlaces->getNrOfGamesSimultaneously();
//    }
//
//    protected function assignHomeAway(
//        AgainstGameRound                                    $gameRound,
//        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway  $homeAway,
//        AmountNrCounterMap $sportAmountNrCounterMap, //&$assignedSportMap,
//        AmountNrCounterMap $amountNrCounterMap, //   &$assignedMap,
//        WithNrCounterMap $withNrCounterMap, //  &$assignedWithMap,
//        AgainstNrCounterMap $againstNrCounterMap, //  &$assignedAgainstMap,
//        SideNrCounterMap $homeNrCounterMap //  &$assignedHomeMap
//    ): void {
//        $sportAmountNrCounterMap->addHomeAway($homeAway);
//        $amountNrCounterMap->addHomeAway($homeAway);
//        $withNrCounterMap->addHomeAway($homeAway);
//        $againstNrCounterMap->addHomeAway($homeAway);
//        $homeNrCounterMap->addHomeAway($homeAway);
//        $gameRound->add($homeAway);
//    }
//
//    protected function releaseHomeAway(
//        AgainstGameRound $gameRound, OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
//    {
//        $gameRound->remove($homeAway);
//    }
//
//
//    /**
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     */
//    protected function outputUnassignedHomeAways(array $homeAways): void
//    {
//        $this->logger->info('unassigned');
//        foreach ($homeAways as $homeAway) {
//            $this->logger->info($homeAway);
//        }
//    }
//
//    /**
//     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
//     */
//    protected function outputUnassignedTotals(array $homeAways): void
//    {
//        $map = [];
//        foreach ($homeAways as $homeAway) {
//            foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
//                if (!isset($map[$placeNr])) {
//                    $map[$placeNr] = new CounterForPlaceNr($placeNr);
//                }
//                $tmpPlaceCounter = $map[$placeNr];
//                $map[$placeNr] = $tmpPlaceCounter->increment();
//            }
//        }
//        foreach ($map as $location => $placeCounter) {
//            $this->logger->info($location . ' => ' . $placeCounter->count());
//        }
//    }
//
////    /**
////     * @param array<string, CounterForDuoPlaceNr> $duoPlacemap
////     * @return array<string, DuoPlaceNr>
////     */
////    protected function convertToDuoPlaceNrMap(array $map): array {
////        $newMap = [];
////        foreach( $map as $idx => $counter ) {
////            $newMap[$idx] = $counter->getDuoPlaceNr();
////        }
////        return $newMap;
////    }
}
