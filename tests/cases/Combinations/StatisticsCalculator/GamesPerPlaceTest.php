<?php

namespace SportsScheduler\Tests\Combinations\StatisticsCalculator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsScheduler\Combinations\AgainstStatisticsCalculators\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsScheduler\Combinations\HomeAwayGenerators\GppHomeAwayGenerator as GppHomeAwayCreator;
use SportsScheduler\Schedules\SportScheduleCreators\Helpers\AgainstDifferenceManager;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSortHomeAway(): void {

        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
        $againstGppMap = [1 => $sportVariant];
        $input = $this->createInput([18], [new SportVariantWithFields($sportVariant, 1)]);
        $poule = $input->getPoule(1);
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $allScheduleMaps = new AllScheduleMaps($poule, [$sportVariant]);
//        $scheduleCreator = new ScheduleCreator($this->createLogger());
//        $inputSports = array_values($input->getSports()->toArray());

//        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($inputSports);
//        $againstGppsWithNr = $scheduleCreator->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
//        if( count($againstGppsWithNr) === 0 ) {
//            return;
//        }

        $allowedGppMargin = $this->calculateMaxGppMargin($poule);

        $differenceManager = new AgainstDifferenceManager(
            count($poule->getPlaces()),
            $againstGppMap,
            $allowedGppMargin,
            $this->createLogger());
        $amountRange = $differenceManager->getAmountRange(1);
        $assignedMap = new RangedPlaceCounterMap($allScheduleMaps->getAmountCounterMap(),$amountRange );
        $againstAmountRange = $differenceManager->getAgainstRange(1);
        $assignedAgainstMap = new RangedPlaceCombinationCounterMap(
            $allScheduleMaps->getAgainstCounterMap(),
            $againstAmountRange );
        $withAmountRange = $differenceManager->getWithRange(1);
        $assignedWithMap = new RangedPlaceCombinationCounterMap(
            $allScheduleMaps->getWithCounterMap() , $withAmountRange);

        $homeAmountRange = $differenceManager->getHomeRange(1);
        $assignedHomeMap = new RangedPlaceCounterMap(
            $allScheduleMaps->getHomeCounterMap(), $homeAmountRange);

        $statisticsCalculator = new GppStatisticsCalculator(
            $variantWithPoule,
            $assignedHomeMap,
            0,
            $assignedMap,
            $assignedAgainstMap,
            $assignedWithMap,
            $this->createLogger()
        );

        $homeAwayCreator = new GppHomeAwayCreator();
        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);

        $time_start = microtime(true);
        $statisticsCalculator->sortHomeAways($homeAways, $this->createLogger());
        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
        self::assertLessThan(10.0, (microtime(true) - $time_start) );
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param Poule $poule
     * @param AgainstGpp $sportVariant
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        Poule $poule,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = (new AgainstGppWithPoule($poule, $sportVariant));
        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($variantWithPoule));
        }
        return $homeAways;
    }
}