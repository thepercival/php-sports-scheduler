<?php

declare(strict_types=1);

namespace SportsScheduler\Schedules\CycleCreators;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;
use SportsScheduler\Schedules\CycleCreators\Helpers\PlaceNrCycleNr;

class CycleCreatorTogether
{
//    protected GameRoundOutput $gameRoundOutput;
//    /**
//     * @var array<string,array<string,PlaceCounter>>
//     */
//    protected array $assignedTogetherMap = [];

    public function __construct(LoggerInterface $logger)
    {
//        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createRootCycleAndGames(
        ScheduleTogetherSport $togetherSportSchedule,
        TogetherNrCounterMap $togetherNrCounterMap
    ): ScheduleCycleTogether {
        $cycleIt = new ScheduleCycleTogether($togetherSportSchedule);
        $nrOfPlaces = $togetherSportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        $placeNrs = (new SportRange(1, $nrOfPlaces))->toArray();
        $availablePlaceNrCycleNrs = [];

        $amountNrCounterMap = new AmountNrCounterMap($nrOfPlaces);
        $togetherSportWithNrOfPlaces = $togetherSportSchedule->createSportWithNrOfPlaces();
        $totalNrOfGamesPerPlace = $togetherSportWithNrOfPlaces->calculateNrOfGamesPerPlace($togetherSportSchedule->nrOfCycles);
        for ($cycleNr = 1 ; $cycleNr <= $totalNrOfGamesPerPlace ; $cycleNr++) {

            $lastCycle = $cycleNr === $totalNrOfGamesPerPlace;
            $placeNrCycleNrs = array_map(function(int $placeNr) use($cycleNr): PlaceNrCycleNr {
                return new PlaceNrCycleNr($placeNr, $cycleNr);
            }, $placeNrs);
            $availablePlaceNrCycleNrs = array_merge($availablePlaceNrCycleNrs, $placeNrCycleNrs);
            // $availablePlaceNrCycleNrs = $this->sortPlaceNrCycleNrs($amountNrCounterMap, $togetherNrCounterMap, $availablePlaceNrCycleNrs);

            $availablePlaceNrCycleNrs = $this->addGamesToCycle(
                $togetherSportSchedule->sport,
                $amountNrCounterMap, $togetherNrCounterMap,
                $availablePlaceNrCycleNrs, $cycleIt
            );

            if ( $lastCycle && count($availablePlaceNrCycleNrs) > 0) {
                $this->createGame($cycleIt->createNext(), $availablePlaceNrCycleNrs, $togetherNrCounterMap);
            }
            if( !$lastCycle ) {
                $cycleIt = $cycleIt->createNext();
            }
        }

        return $cycleIt->getFirst();
    }

    /**
     * @param TogetherSport $togetherSport
     * @param AmountNrCounterMap $amountNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param list<PlaceNrCycleNr> $availablePlaceNrCycleNrs
     * @param ScheduleCycleTogether $togetherCycle
     * @return list<PlaceNrCycleNr>
     */
    protected function addGamesToCycle(
        TogetherSport $togetherSport,
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $availablePlaceNrCycleNrs,
        ScheduleCycleTogether $togetherCycle
    ): array {
        $placeNrCycleNrsForGame = [];

        while (
            $bestPlaceNrCycleNr = $this->removeBestPlaceNrCycleNr($togetherNrCounterMap, $availablePlaceNrCycleNrs)
        ) {
            $placeNrCycleNrsForGame[] = $bestPlaceNrCycleNr;
            if (count($placeNrCycleNrsForGame) === $togetherSport->getNrOfGamePlaces()) {
                $this->createGame($togetherCycle, $placeNrCycleNrsForGame, $togetherNrCounterMap);
                $placeNrCycleNrsForGame = [];
            }
        }
        return $placeNrCycleNrsForGame;
    }

    protected function addGameToTogetherNrCounterMap(
        ScheduleGameTogether $game,
        TogetherNrCounterMap $togetherNrCounterMap): void {
        foreach( $game->convertToDuoPlaceNrs() as $duoPlaceNr ) {
            $togetherNrCounterMap->incrementDuoPlaceNr($duoPlaceNr);
        }
    }

    /**
     * @param AmountNrCounterMap $amountNrCounterMap
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param list<PlaceNrCycleNr> $placeNrCycleNrs
     * @return list<PlaceNrCycleNr>
     */
    protected function sortPlaceNrCycleNrs(
        AmountNrCounterMap $amountNrCounterMap,
        TogetherNrCounterMap $togetherNrCounterMap,
        array $placeNrCycleNrs): array
    {
        uasort(
            $placeNrCycleNrs,
            function (PlaceNrCycleNr $placeNrCycleNrA, PlaceNrCycleNr $placeNrCycleNrB)
                use ($amountNrCounterMap, $togetherNrCounterMap, $placeNrCycleNrs): int
            {
                $nrOfAssignedGamesA = $amountNrCounterMap->count($placeNrCycleNrA->placeNr);
                $nrOfAssignedGamesB = $amountNrCounterMap->count($placeNrCycleNrB->placeNr);
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placeCycleNrsToCompareA = $this->getOtherPlaceCycleNrs($placeNrCycleNrA->placeNr, $placeNrCycleNrs);
                $scoreA = $this->getScore($togetherNrCounterMap, $placeNrCycleNrA, $placeCycleNrsToCompareA);
                $placeCycleNrsToCompareB = $this->getOtherPlaceCycleNrs($placeNrCycleNrB->placeNr, $placeNrCycleNrs);
                $scoreB = $this->getScore($togetherNrCounterMap, $placeNrCycleNrA, $placeCycleNrsToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($placeNrCycleNrs);
    }

    /**
     * @param TogetherNrCounterMap $togetherCounterMap
     * @param list<PlaceNrCycleNr> $placeNrCycleNrs
     * @return PlaceNrCycleNr
     */
    protected function removeBestPlaceNrCycleNr(
        TogetherNrCounterMap $togetherCounterMap,
        array &$placeNrCycleNrs
    ): PlaceNrCycleNr|null {
        $bestPlaceNrCycleNr = null;
        $lowestScore = null;
        foreach ($placeNrCycleNrs as $placeNrCycleNr) {
            $score = $this->getScore($togetherCounterMap, $placeNrCycleNr, $placeNrCycleNrs);
            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $bestPlaceNrCycleNr = $placeNrCycleNr;
            }
        }
        $idx = array_search($bestPlaceNrCycleNr, $placeNrCycleNrs, true);
        if ($idx !== false) {
            array_splice($placeNrCycleNrs, $idx, 1);
        }
        return $bestPlaceNrCycleNr;
    }

    /**
     * @param int $placeNrCycleNr
     * @param list<PlaceNrCycleNr> $placeNrCycleNrs
     * @return list<PlaceNrCycleNr>
     */
    protected function getOtherPlaceCycleNrs(int $placeNrCycleNr, array $placeNrCycleNrs): array
    {
        $idx = array_search($placeNrCycleNr, $placeNrCycleNrs, false);
        if ($idx === false) {
            return $placeNrCycleNrs;
        }
        array_splice($placeNrCycleNrs, $idx, 1);
        return $placeNrCycleNrs;
    }

    /**
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @param PlaceNrCycleNr $placeNrCycleNr
     * @param list<PlaceNrCycleNr> $placeNrCycleNrs
     * @return int
     */
    protected function getScore(TogetherNrCounterMap $togetherNrCounterMap, PlaceNrCycleNr $placeNrCycleNr, array $placeNrCycleNrs): int
    {
        $score = 0;
        foreach ($placeNrCycleNrs as $placeNrCycleNrsIt) {
            if ($placeNrCycleNr->placeNr === $placeNrCycleNrsIt->placeNr) {
                return 100000;
            }
            $duoPlaceNr = new DuoPlaceNr($placeNrCycleNr->placeNr, $placeNrCycleNrsIt->placeNr);
            $score += $togetherNrCounterMap->count($duoPlaceNr);
        }
        return $score;
    }

    /**
     * @param ScheduleCycleTogether $cycle
     * @param list<PlaceNrCycleNr> $placeNrCycleNrs
     * @param TogetherNrCounterMap $togetherNrCounterMap
     * @return void
     */
    protected function createGame(
        ScheduleCycleTogether $cycle,
        array $placeNrCycleNrs,
        TogetherNrCounterMap $togetherNrCounterMap
    ): void {
        $game = new ScheduleGameTogether($cycle);
        while( $placeNrCycleNr = array_shift($placeNrCycleNrs) ) {
            new ScheduleGamePlaceTogether(
                $game, $placeNrCycleNr->placeNr, $placeNrCycleNr->cycleNr
            );
        }
        // add games to maps
        $this->addGameToTogetherNrCounterMap($game, $togetherNrCounterMap);
    }
}
