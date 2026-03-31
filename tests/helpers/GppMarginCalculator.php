<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Psr\Log\LoggerInterface;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;

trait GppMarginCalculator
{
    /**
     * @param int $nrOfPlaces
     * @param list<AgainstGpp|AgainstH2h|Single|AllInOneGame> $sportVariants
     * @param LoggerInterface $logger
     * @return int
     */
    protected function getMaxGppMargin(int $nrOfPlaces, array $sportVariants, LoggerInterface $logger): int {
        $scheduleCreator = new ScheduleCreator($logger);
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        return $scheduleCreator->getMaxGppMargin($sportVariantsWithNr, $nrOfPlaces);
    }
}
