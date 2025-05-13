<?php

declare(strict_types=1);

namespace SportsScheduler\Game;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;

class PlannableGameCreator
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Planning $planning
     * @param array<int, list<ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo>> $sportCyclesMap
     */
    public function createGamesFromCycles(Planning $planning, array $sportCyclesMap): void
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            $sportCycles = $sportCyclesMap[count($poule->getPlaces())];
            foreach ($sportCycles as $sportCycle) {
                $this->createSportGames($planning, $poule, $sportCycle);
            }
        }
    }

//    /**
//     * @param list<Schedule> $schedules
//     * @param Poule $poule
//     * @param ScheduleCycleTogether|ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo $sportCycle
//     * @return ScheduleSport
//     */
//    protected function getScheduleSport(array $schedules, Poule $poule, Sport $sport): ScheduleSport
//    {
//        $nrOfPlaces = $poule->getPlaces()->count();
//        foreach ($schedules as $schedule) {
//            if ($schedule->getNrOfPlaces() !== $nrOfPlaces) {
//                continue;
//            }
//            foreach ($schedule->getSportSchedules() as $sportSchedule) {
//                if ($sportSchedule->getNumber() === $sport->getNumber()) {
//                    return $sportSchedule;
//                }
//            }
//        }
//        throw new \Exception('could not find sport-gameround-schedule for nfOfPlace: ' . $nrOfPlaces . ', and sport: "' . $sport->createVariant() . '"', E_ERROR);
//    }

    protected function createSportGames(
        Planning $planning,
        Poule $poule,
        ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo|ScheduleCycleTogether $sportRootCycle
    ): void
    {
        if ($sportRootCycle instanceof ScheduleCycleTogether) {
            $this->createTogetherGames($planning, $poule, $sportRootCycle);
        } else {
            $this->createAgainstGames($planning, $poule, $sportRootCycle);
        }
    }


    protected function createTogetherGames(
        Planning $planning, Poule $poule, ScheduleCycleTogether $sportCycle
    ): void
    {
        $plannableSport = $planning->getInput()->getSport($sportCycle->sportSchedule->number);
        $defaultField = $plannableSport->getField(1);

        foreach ($sportCycle->getGames() as $scheduleGame) {
            $game = new TogetherGame($planning, $poule, $defaultField);
            foreach ($scheduleGame->getGamePlaces() as $scheduleGamePlace) {
                $place = $poule->getPlace($scheduleGamePlace->placeNr);
                new TogetherGamePlace($game, $place, $scheduleGamePlace->cycleNr);
            }
        }

        $nextSportCycle = $sportCycle->getNext();
        if ($nextSportCycle !== null) {
            $this->createTogetherGames($planning, $poule, $nextSportCycle);
        }
    }

    protected function createAgainstGames(
        Planning $planning, Poule $poule,
        ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo $sportCycle
    ): void
    {
        $plannableSport = $planning->getInput()->getSport($sportCycle->sportSchedule->number);
        $defaultField = $plannableSport->getField(1);

        $cyclePart = $sportCycle->firstPart;
        while ($cyclePart !== null) {
            foreach ($cyclePart->getGamesAsHomeAways() as $homeAwayGame ) {
                $game = new AgainstGame($planning, $poule, $defaultField, $cyclePart->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    $sidePlaces = array_map(function (int $placeNr) use ($poule): Place {
                        return $poule->getPlace($placeNr);
                    }, $homeAwayGame->convertToPlaceNrs($side) );
                    foreach ($sidePlaces as $place) {
                        new AgainstGamePlace($game, $place, $side);
                    }
                }
            }
            $cyclePart = $cyclePart->getNext();
        }

        $nextSportCycle = $sportCycle->getNext();
        if ($nextSportCycle !== null) {
            $this->createAgainstGames($planning, $poule, $nextSportCycle);
        }
    }
}