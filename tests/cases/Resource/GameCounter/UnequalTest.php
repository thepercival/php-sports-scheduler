<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Resource\GameCounter;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Resource\GameCounter\Place as PlaceCounter;
use SportsScheduler\Resource\GameCounter\Unequal;
use SportsScheduler\TestHelper\PlanningCreator;
// use SportsPlanning\TestHelper\PlanningReplacer;

class UnequalTest extends TestCase
{
    use PlanningCreator;
    // use PlanningReplacer;

    public function testCalculations(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3])
        );

        $placeOne = $planning->getInput()->getPoule(1)->getPlace(1);
        $placeTwo = $planning->getInput()->getPoule(1)->getPlace(2);
        $gameCounterPlaceOne = new PlaceCounter($placeOne);
        $gameCounterPlaceTwo = new PlaceCounter($placeTwo);

        $unequal = new Unequal(1, [$gameCounterPlaceOne], 3, [$gameCounterPlaceTwo]);
        $unequal->setPouleNr(1);
        self::assertSame(1, $unequal->getPouleNr());

        self::assertSame(1, $unequal->getMinNrOfGames());
        self::assertSame(3, $unequal->getMaxNrOfGames());
        self::assertSame(2, $unequal->getDifference());

        self::assertSame([$gameCounterPlaceOne], $unequal->getMinGameCounters());
        self::assertSame([$gameCounterPlaceTwo], $unequal->getMaxGameCounters());
    }
}
