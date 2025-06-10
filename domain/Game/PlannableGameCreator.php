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

final class PlannableGameCreator
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
        foreach ($planning->poules as $poule) {
            $sportCycles = $sportCyclesMap[count($poule->places)];
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
        $sportWithNrAndFields = $planning->getSport($sportCycle->sportSchedule->number);
        $defaultField = $sportWithNrAndFields->getField(1);

        foreach ($sportCycle->getGames() as $scheduleGame) {
            $game = TogetherGame::fromPoule($poule, $defaultField);
            foreach ($scheduleGame->getGamePlaces() as $scheduleGamePlace) {
                $place = $poule->getPlace($scheduleGamePlace->placeNr);
                $game->addGamePlace( new TogetherGamePlace($place->placeNr, $scheduleGamePlace->cycleNr) );
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
        $sportWithNrAndFields = $planning->getSport($sportCycle->sportSchedule->number);
        $defaultField = $sportWithNrAndFields->getField(1);

        $cyclePart = $sportCycle->firstPart;
        while ($cyclePart !== null) {
            foreach ($cyclePart->getGamesAsHomeAways() as $homeAwayGame ) {
                $game = AgainstGame::fromPoule($poule, $defaultField, $cyclePart->getNumber(), $sportCycle->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    $sidePlaces = array_map(function (int $placeNr) use ($poule): Place {
                        return $poule->getPlace($placeNr);
                    }, $homeAwayGame->convertToPlaceNrs($side) );
                    foreach ($sidePlaces as $place) {
                        $game->addGamePlace($side, $place->placeNr);
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