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
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstGppHomeAwayCreator as HomeAwayCreator;
use SportsScheduler\TestHelper\PlanningCreator;

/**
 * @psalm-suppress InvalidReturnType
 */
final class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1(): void
    {
        $nrOfPlaces = 5;
        $sportVariant = new AgainstGpp(1, 1, 1);

        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        // (new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(10, $homeAways);
    }

    public function testSimple1VS2Pl3(): void
    {
        $nrOfPlaces = 3;
        $sportVariant = new AgainstGpp(1, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS2Pl4(): void
    {
        $nrOfPlaces = 4;
        $sportVariant = new AgainstGpp(1, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(12, $homeAways);
    }

    public function testSimple2VS2Pl4(): void
    {
        $nrOfPlaces = 4;
        $sportVariant = new AgainstGpp(2, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple2VS2Pl5(): void
    {
        $nrOfPlaces = 5;
        $sportVariant = new AgainstGpp(2, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(15, $homeAways);
    }

    public function testSimple2VS2Pl6(): void
    {
        $nrOfPlaces = 6;
        $sportVariant = new AgainstGpp(2, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(45, $homeAways);
    }

    public function testSimple2VS2Pl7(): void
    {
        $nrOfPlaces = 7;
        $sportVariant = new AgainstGpp(2, 2, 1);
        $creator = new HomeAwayCreator();
        $variantWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $sportVariant);
        $homeAways = $creator->create($variantWithNrOfPlaces);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(105, $homeAways);
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
