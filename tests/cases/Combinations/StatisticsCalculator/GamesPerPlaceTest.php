<?php

namespace SportsScheduler\Tests\Combinations\StatisticsCalculator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCounterMap;
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstGppHomeAwayCreator as GppHomeAwayCreator;
use SportsScheduler\Combinations\StatisticsCalculators\AgainstGppStatisticsCalculator as GppStatisticsCalculator;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testSortHomeAway(): void {

        $sportVariant = $this->getAgainstGppSportVariant(2, 2, 26);
        $nrOfPlaces = 18;
        $variantWithPoule = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $assignedCounter = new AssignedCounter($nrOfPlaces, [$sportVariant]);
        $scheduleCreator = new ScheduleCreator($this->getLogger());
        // $inputSports = array_values($input->getSports()->toArray());
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr([$sportVariant]);
        $againstGppsWithNr = $scheduleCreator->getAgainstGppSportVariantsWithNr($sportVariantsWithNr, $nrOfPlaces);
        if( count($againstGppsWithNr) === 0 ) {
            return;
        }

        $allowedGppMargin = $this->getMaxGppMargin($nrOfPlaces, [$sportVariant], $this->getLogger() );

        $differenceManager = new AgainstDifferenceManager(
            $nrOfPlaces,
            $againstGppsWithNr,
            $allowedGppMargin,
            $this->getLogger());
        $amountRange = $differenceManager->getAmountRange(1);
        $assignedMap = new RangedPlaceNrCounterMap($assignedCounter->getAssignedMap(),$amountRange );
        $againstAmountRange = $differenceManager->getAgainstRange(1);
        $assignedAgainstMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedAgainstMap(),
            $againstAmountRange );
        $withAmountRange = $differenceManager->getWithRange(1);
        $assignedWithMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedWithMap() , $withAmountRange);

        $homeAmountRange = $differenceManager->getHomeRange(1);
        $assignedHomeMap = new RangedPlaceNrCombinationCounterMap(
            $assignedCounter->getAssignedHomeMap(), $homeAmountRange);

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
        $homeAways = $this->createHomeAways($homeAwayCreator, $nrOfPlaces, $sportVariant);

        $time_start = microtime(true);
        $statisticsCalculator->sortHomeAways($homeAways, $this->getLogger());
        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
        self::assertLessThan(10.0, (microtime(true) - $time_start) );
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param int $nrOfPlaces
     * @param AgainstGpp $sportVariant
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        int $nrOfPlaces,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = (new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant));
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

        $handler = new StreamHandler('php://stdout', LogLevel::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}