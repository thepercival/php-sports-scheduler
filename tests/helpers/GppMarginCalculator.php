<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;

use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Poule;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Schedule\Creator as ScheduleCreator;

trait GppMarginCalculator
{
    protected function getMaxGppMargin(Poule $poule, LoggerInterface $logger): int {
        $sports = array_values($poule->getInput()->getSports()->toArray());

        $scheduleCreator = new ScheduleCreator($logger);
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sports);
        $nrOfPlaces = count($poule->getPlaces());
        return $scheduleCreator->getMaxGppMargin($sportVariantsWithNr, $nrOfPlaces);
    }
}
