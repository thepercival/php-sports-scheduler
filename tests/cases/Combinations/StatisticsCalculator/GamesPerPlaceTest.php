<?php

namespace SportsScheduler\Tests\Combinations\StatisticsCalculator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsScheduler\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsScheduler\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\Input;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Poule;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSortHomeAway(): void {

        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
        $input = $this->createInput([18], [new SportVariantWithFields($sportVariant, 1)]);
        $poule = $input->getPoule(1);
        $nrOfPlaces = count($poule->getPlaces());
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $allScheduleMaps = new AllScheduleMaps($poule, [$sportVariant]);
        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $inputSports = array_values($input->getSports()->toArray());
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($inputSports);
        $againstGppsWithNr = $scheduleCreator->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
        if( count($againstGppsWithNr) === 0 ) {
            return;
        }

        $allowedGppMargin = $this->getMaxGppMargin($poule, $this->getLogger() );

        $differenceManager = new AgainstDifferenceManager(
            $poule,
            $againstGppsWithNr,
            $allowedGppMargin,
            $this->getLogger());
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
            $this->getLogger()
        );

        $homeAwayCreator = new GppHomeAwayCreator();
        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);

        $time_start = microtime(true);
        $statisticsCalculator->sortHomeAways($homeAways, $this->getLogger());
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

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}