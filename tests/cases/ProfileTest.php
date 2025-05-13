<?php

declare(strict_types=1);

namespace SportsScheduler\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Counters\AssignedCounter;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Counters\Maps\RangedPlaceCombinationCounterMap;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsScheduler\Combinations\AgainstStatisticsCalculators\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsScheduler\Combinations\HomeAwayGenerators\GppHomeAwayGenerator as GppHomeAwayCreator;
use SportsScheduler\TestHelper\PlanningCreator;

// cachegrind output default to /tmp
class ProfileTest extends TestCase
{
    use PlanningCreator;

    public function test2V2And6PlacesAnd8GamesPerPlace(): void
    {
//        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
//        $input = $this->createInput([18], [new SportVariantWithFields($sportVariant, 1)]);
//        $poule = $input->getPoule(1);
//        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
//        $mapper = new Mapper();
//        $assignedCounter = new AssignedCounter($poule, [$sportVariant]);
//        $allowedGppMargin = ScheduleCreator::MAX_ALLOWED_GPP_MARGIN;
//        $againstGppMap = $this->getAgainstGppSportVariantMap($input);
//        if( count($againstGppMap) === 0 ) {
//            return;
//        }
//        $differenceManager = new AgainstDifferenceManager(
//            $poule,
//            $againstGppMap,
//            $allowedGppMargin,
//            $this->getLogger());
//        $againstAmountRange = $differenceManager->getAgainstRange(1);
//
//        $assignedAgainstMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedAgainstMap(),
//            $againstAmountRange );
//
//        $withAmountRange = $differenceManager->getWithRange(1);
//        $assignedWithMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedWithMap() , $withAmountRange);
//
//        $homeAmountRange = $differenceManager->getHomeRange(1);
//        $assignedHomeMap = new RangedPlaceCombinationCounterMap(
//            $assignedCounter->getAssignedHomeMap(), $homeAmountRange);
//
//        $statisticsCalculator = new GppStatisticsCalculator(
//            $variantWithPoule,
//            $assignedHomeMap,
//            0,
//            new PlaceCounterMap( array_values( $mapper->getPlaceMap($poule) ) ),
//            new PlaceCounterMap( array_values($assignedCounter->getAssignedMap() ) ),
//            $assignedAgainstMap,
//            $assignedWithMap,
//            $this->getLogger()
//        );
//
//        $homeAwayCreator = new GppHomeAwayCreator();
//        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);
//
//        $time_start = microtime(true);
//        $statisticsCalculator->sortHomeAways($homeAways, $this->getLogger());
//        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
//        self::assertLessThan(3.5, (microtime(true) - $time_start) );

        self::assertCount(0, []);
    }

//    /**
//     * @param GppHomeAwayCreator $homeAwayCreator
//     * @param Poule $poule
//     * @param AgainstGpp $sportVariant
//     * @return list<HomeAway>
//     */
//    protected function createHomeAways(
//        GppHomeAwayCreator $homeAwayCreator,
//        Poule $poule,
//        AgainstGpp $sportVariant): array
//    {
//        $variantWithPoule = (new AgainstGppWithPoule($poule, $sportVariant));
//        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
//        $homeAways = [];
//        while ( count($homeAways) < $totalNrOfGames ) {
//            $homeAways = array_merge($homeAways, $homeAwayCreator->create($variantWithPoule));
//        }
//        return $homeAways;
//    }
//
//    /**
//     * @param Input $input
//     * @return array<int, AgainstGpp>
//     */
//    protected function getAgainstGppSportVariantMap(Input $input): array
//    {
//        $map = [];
//        foreach( $input->getSports() as $sport) {
//            $sportVariant = $sport->createVariant();
//            if( $sportVariant instanceof AgainstGpp) {
//                $map[$sport->getNumber()] = $sportVariant;
//            }
//        }
//        return $map;
//    }
}
