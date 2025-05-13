<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Poule;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsScheduler\TestHelper\PlanningCreator;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator;

    public function testCalculations(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $refereeInfo = new PlanningRefereeInfo();
        $planning = $this->createPlanning(
            new Configuration(new PouleStructure(3), $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo, false)
        );

        $pouleOne = $planning->getInput()->getPoule(1);
        $pouleCounter = new GamePlacesCounterForPoule($pouleOne);

        $nrOfPlacesAssigned = 3;
        $pouleCounter = $pouleCounter->add($nrOfPlacesAssigned);

        self::assertSame($nrOfPlacesAssigned, $pouleCounter->getNrOfPlacesAssigned());
        self::assertSame(1, $pouleCounter->getNrOfGames());

        $pouleCounter = $pouleCounter->reset();
        self::assertSame(0, $pouleCounter->getNrOfPlacesAssigned());
        self::assertSame(0, $pouleCounter->getNrOfGames());

        self::assertSame($pouleOne, $pouleCounter->getPoule());
    }
}
