<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstGppHomeAwayCreator as GppHomeAwayCreator;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleAgainstCreatorHelperAbstract as AgainstHelper;
use SportsScheduler\Schedule\GameRoundCreators\ScheduleAgainstGppGameRoundCreator;
use SportsScheduler\Schedule\SportVariantWithNr;

final class ScheduleAgainstGppCreatorHelper extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param int $nrOfPlaces,
     * @param list<SportVariantWithNr> $againstGppsWithNr
     * @param AssignedCounter $assignedCounter
     * @param AgainstDifferenceManager $againstGppDifferenceManager,
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule                 $schedule,
        int                      $nrOfPlaces,
        array                    $againstGppsWithNr,
        AssignedCounter          $assignedCounter,
        AgainstDifferenceManager $againstGppDifferenceManager,
        int|null                 $nrOfSecondsBeforeTimeout
    ): void
    {
        $homeAwayCreator = new GppHomeAwayCreator();

        foreach ($againstGppsWithNr as $againstGppWithNr) {
            $sportNr = $againstGppWithNr->number;
            $sportVariant = $againstGppWithNr->sportVariant;
            if( !($sportVariant instanceof AgainstGpp ) ) {
                continue;
            }
            $sportSchedule = new ScheduleSport($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new ScheduleAgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $nrOfPlaces,
                $sportVariant,
                $homeAwayCreator,
                $assignedCounter,
                $againstGppDifferenceManager->getAmountRange($sportNr),
                $againstGppDifferenceManager->getAgainstRange($sportNr),
                $againstGppDifferenceManager->getWithRange($sportNr),
                $againstGppDifferenceManager->getHomeRange($sportNr),
                $nrOfSecondsBeforeTimeout
            );

            $this->createGames($sportSchedule, $gameRound);
            $assignedCounter->assignHomeAways($gameRound->getAllHomeAways());
        }
    }
}
