<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\HomeAwayCreator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\SportVariant\AgainstH2hWithNrOfPlaces;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstH2hHomeAwayCreator as HomeAwayCreator;
use SportsScheduler\TestHelper\PlanningCreator;

final class H2hTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1Pl2(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2h(new AgainstH2hWithNrOfPlaces(2, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(1, $homeAways);
    }

    public function testSimple1VS1Pl3(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2h(new AgainstH2hWithNrOfPlaces(3, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS1Pl4(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2h(new AgainstH2hWithNrOfPlaces(4, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(6, $homeAways);
    }

    public function testSimple1VS1Pl5(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2h(new AgainstH2hWithNrOfPlaces(5, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(10, $homeAways);
    }

//    public function test1Poule12Places(): void
//    {
//        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
//        $input = $this->createInput([7]);
//        $poule = $input->getPoule(1);
//        $creator = new HomeAwayCreatorAbstract($poule, $sportVariant);
//        $homeAways = $creator->createForOneH2h();
//        (new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
//        (new HomeAwayOutput($this->getLogger()))->outputTotals($homeAways);
//        // self::assertCount(66, $homeAways);
//
    ////        $place11 = $poule->getPlace(11);
    ////        $homes = array_filter($homeAways, fn ($homeAway) => $homeAway->getHome()->has($place11));
    ////
    ////        self::assertCount(6, $homes);
//    }

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
