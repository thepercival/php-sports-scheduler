<?php

declare(strict_types=1);

namespace SportsScheduler\Schedule\CreatorHelpers;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstH2hHomeAwayCreator;
use SportsScheduler\Combinations\HomeAwayCreators\AgainstH2HHomeAwayCreator as H2hHomeAwayCreator;
use SportsScheduler\Schedule\CreatorHelpers\ScheduleAgainstCreatorHelperAbstract as AgainstHelper;
use SportsScheduler\Schedule\GameRoundCreators\ScheduleAgainstH2hGameRoundCreator;
use SportsScheduler\Schedule\SportVariantWithNr;

final class ScheduleAgainstH2hCreatorHelper extends AgainstHelper
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param Schedule $schedule
     * @param int $nrOfPlaces
     * @param list<SportVariantWithNr> $againstH2hsWithNr
     * @param AssignedCounter $assignedCounter
     * @param AgainstDifferenceManager $againstGppDifferenceManager
     * @throws Exception
     */
    public function createScheduleSports(
        Schedule $schedule,
        int $nrOfPlaces,
        array $againstH2hsWithNr,
        AssignedCounter $assignedCounter,
        AgainstDifferenceManager $againstGppDifferenceManager
    ): void
    {
        $homeAwayCreator = new AgainstH2hHomeAwayCreator();
        $sportNr = 1;
        foreach ($againstH2hsWithNr as $againstH2hWithNr) {
            $sportVariant = $againstH2hWithNr->sportVariant;
            if( !($sportVariant instanceof AgainstH2h ) ) {
                continue;
            }
            $scheduleSport = new ScheduleSport($schedule, $sportNr, $sportVariant->toPersistVariant());

            $gameRoundCreator = new ScheduleAgainstH2hGameRoundCreator($this->logger);
            $gameRound = $gameRoundCreator->createGameRound(
                $nrOfPlaces,
                $sportVariant,
                $homeAwayCreator,
                $assignedCounter,
                $againstGppDifferenceManager->getHomeRange($sportNr)
            );

            $this->createGames($scheduleSport, $gameRound);
        }
    }

//    public function setGamesPerPlaceMargin(int $margin): void {
//        $this->gamesPerPlaceMargin = $margin;
//    }



}
