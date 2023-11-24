<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers\Against;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsScheduler\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsScheduler\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsScheduler\GameRound\Creator\Against\GamesPerPlace as AgainstGppGameRoundCreator;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsScheduler\Schedule\CreatorHelpers\Against as AgainstHelper;
use SportsScheduler\Schedule\SportVariantWithNr;

class GamesPerPlace extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<SportVariantWithNr> $againstGppsWithNr
     * @param AssignedCounter $assignedCounter
     * @param AgainstDifferenceManager $againstGppDifferenceManager,
     * @param int|null $nrOfSecondsBeforeTimeout
     * @throws Exception
     */
    public function createSportSchedules(
        Schedule                 $schedule,
        Poule                    $poule,
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
            $sportSchedule = new SportSchedule($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new AgainstGppGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $poule,
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
