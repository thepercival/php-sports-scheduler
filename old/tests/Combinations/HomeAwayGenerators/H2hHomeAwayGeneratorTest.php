<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Combinations\HomeAwayGenerators;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\VariantWithFields;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\Maps\RangedDuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\RangedPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Output\Combinations\HomeAwayOutput;
use SportsScheduler\Combinations\HomeAwayGenerators\H2hHomeAwayGenerator;
use SportsScheduler\TestHelper\LoggerCreator;

class H2hHomeAwayGeneratorTest extends TestCase
{
    use LoggerCreator;

    public function testNrOfPlaces2(): void
    {
        $nrOfPlaces = 2;
        $generator = new H2hHomeAwayGenerator();
        $homeAways = $generator->createForOneH2h($nrOfPlaces, false);
        (new HomeAwayOutput($this->createLogger()))->outputHomeAways($homeAways);
        self::assertCount(1, $homeAways);
        $homeAway = reset($homeAways);
        self::assertNotFalse($homeAway);
        self::assertSame(1, $homeAway->getHome());
        self::assertSame(2, $homeAway->getAway());
    }

    public function testNrOfPlaces2Swapped(): void
    {
        $nrOfPlaces = 2;
        $generator = new H2hHomeAwayGenerator();
        $homeAways = $generator->createForOneH2h($nrOfPlaces, true);
        (new HomeAwayOutput($this->createLogger()))->outputHomeAways($homeAways);
        self::assertCount(1, $homeAways);
        $homeAway = reset($homeAways);
        self::assertNotFalse($homeAway);
        self::assertSame(2, $homeAway->getHome());
        self::assertSame(1, $homeAway->getAway());
    }

    public function testWithinRange_4(): void
    {
        $nrOfPlaces = 4;
        $generator = new H2hHomeAwayGenerator();
        $homeAways = $generator->createForOneH2h($nrOfPlaces, true);
        // (new HomeAwayOutput($this->createLogger()))->outputHomeAways($homeAways);

        $allMaps = new AllScheduleMaps($nrOfPlaces);
        $allMaps->addHomeAways($homeAways);

        $allowedAmountRange = new AmountRange(new AmountBoundary(3,4), new AmountBoundary(3,4));
        $rangedAmountNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getAmountCounterMap(), $allowedAmountRange);
        self::assertTrue($rangedAmountNrPlaceCounterMap->withinRange(0));

        $allowedSideRange = new AmountRange(new AmountBoundary(1,2), new AmountBoundary(2,2));
        $rangedHomeNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getHomeCounterMap(), $allowedSideRange);
        self::assertTrue($rangedHomeNrPlaceCounterMap->withinRange(0));

        $rangedAwayNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getAwayCounterMap(), $allowedSideRange);
        self::assertTrue($rangedAwayNrPlaceCounterMap->withinRange(0));

        $allowedRange = new AmountRange(new AmountBoundary(1,6), new AmountBoundary(1,6));
        $rangedTogetherNrPlaceCounterMap = new RangedDuoPlaceNrCounterMap($allMaps->getTogetherCounterMap(), $allowedRange);
        self::assertTrue($rangedTogetherNrPlaceCounterMap->withinRange(0));

        $allowedAgainstRange = new AmountRange(new AmountBoundary(1,6), new AmountBoundary(1,6));
        $rangedAgainstNrPlaceCounterMap = new RangedDuoPlaceNrCounterMap($allMaps->getAgainstCounterMap(), $allowedAgainstRange);
        self::assertTrue($rangedAgainstNrPlaceCounterMap->withinRange(0));
    }

    public function testWithinRange_5(): void
    {
        $nrOfPlaces = 5;
        $generator = new H2hHomeAwayGenerator();
        $homeAways = $generator->createForOneH2h($nrOfPlaces, true);
        // (new HomeAwayOutput($this->createLogger()))->outputHomeAways($homeAways);

        $allMaps = new AllScheduleMaps($nrOfPlaces);
        $allMaps->addHomeAways($homeAways);

        $allowedAmountRange = new AmountRange(new AmountBoundary(4,5), new AmountBoundary(4,5));
        $rangedAmountNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getAmountCounterMap(), $allowedAmountRange);
        self::assertTrue($rangedAmountNrPlaceCounterMap->withinRange(0));

        $allowedSideRange = new AmountRange(new AmountBoundary(2,5), new AmountBoundary(2,5));
        $rangedHomeNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getHomeCounterMap(), $allowedSideRange);
        self::assertTrue($rangedHomeNrPlaceCounterMap->withinRange(0));

        $rangedAwayNrPlaceCounterMap = new RangedPlaceNrCounterMap($allMaps->getAwayCounterMap(), $allowedSideRange);
        self::assertTrue($rangedAwayNrPlaceCounterMap->withinRange(0));

        $allowedTogetherRange = new AmountRange(new AmountBoundary(1,10), new AmountBoundary(1,10));
        $rangedTogetherNrPlaceCounterMap = new RangedDuoPlaceNrCounterMap($allMaps->getTogetherCounterMap(), $allowedTogetherRange);
        self::assertTrue($rangedTogetherNrPlaceCounterMap->withinRange(0));

        $allowedAgainstRange = new AmountRange(new AmountBoundary(1,10), new AmountBoundary(1,10));
        $rangedAgainstNrPlaceCounterMap = new RangedDuoPlaceNrCounterMap($allMaps->getAgainstCounterMap(), $allowedAgainstRange);
        self::assertTrue($rangedAgainstNrPlaceCounterMap->withinRange(0));
    }

//    public function testSimple1VS1Pl4(): void
//    {
//        $sportVariant = new AgainstH2h(1, 1, 1);
//        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
//        $input = $this->createInput([4],[$sportVariantWithFields]);
//        $poule = $input->getPoule(1);
//        $creator = new HomeAwayCreator();
//        $homeAways = $creator->createForOneH2h(new AgainstH2hWithPoule($poule, $sportVariant));
//        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
//        self::assertCount(6, $homeAways);
//    }
//
//    public function testSimple1VS1Pl5(): void
//    {
//        $sportVariant = new AgainstH2h(1, 1, 1);
//        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
//        $input = $this->createInput([5], [$sportVariantWithFields]);
//        $poule = $input->getPoule(1);
//        $creator = new HomeAwayCreator();
//        $homeAways = $creator->createForOneH2h(new AgainstH2hWithPoule($poule, $sportVariant));
//        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
//        self::assertCount(10, $homeAways);
//    }

//    public function test1Poule12Places(): void
//    {
//        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
//        $input = $this->createInput([7]);
//        $poule = $input->getPoule(1);
//        $creator = new HomeAwayCreator($poule, $sportVariant);
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
}
