<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule\CreatorHelpers;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsScheduler\Schedule\SportScheduleCreators\Helpers\AgainstDifferenceManager;
use SportsScheduler\Schedule\SportVariantWithNr;
use SportsScheduler\TestHelper\PlanningCreator;

class AgainstDifferenceManagerTest extends TestCase
{
    use PlanningCreator;

    public function testMultipleUnknown(): void
    {
        $againstWithNr = [
            new SportVariantWithNr(1, new AgainstGpp(1, 1, 9)),
            new SportVariantWithNr(2, new AgainstGpp(1, 1, 9)),
            new SportVariantWithNr(3, new AgainstGpp(1, 1, 9))
        ];

        $differenceManager = new AgainstDifferenceManager(10, $againstWithNr, 0, $this->createLogger());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $homeRangeSport1 = $differenceManager->getHomeRange(1);
        $homeRangeSport2 = $differenceManager->getHomeRange(2);
        $homeRangeSport3 = $differenceManager->getHomeRange(3);
        self::assertSame('4.5', (string)$homeRangeSport1->getMin());
        self::assertSame('9.0', (string)$homeRangeSport2->getMin());
        self::assertSame('13.5', (string)$homeRangeSport3->getMin());
    }
}
