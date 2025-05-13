<?php

namespace SportsScheduler\TestHelper;

use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;

trait ScheduleHelper
{
    protected function getTogetherGames(
        ScheduleCycleTogether $rootCycle
    ): array {
        $games = [];
        $cycle = $rootCycle;
        while($cycle !== null) {
            foreach ($cycle->getGames() as $game) {
                $games[] = $game;
            }
            $cycle = $cycle->getNext();
        }
        return $games;
    }

    protected function getAgainstOneVsOneGames(
        ScheduleCycleAgainstOneVsOne $rootCycle
    ): array {
        $games = [];
        $cycle = $rootCycle;
        while($cycle !== null) {
            foreach ($cycle->getAllCyclePartGames() as $game) {
                $games[] = $game;
            }
            $cycle = $cycle->getNext();
        }
        return $games;
    }

    protected function getAgainstOneVsTwoGames(
        ScheduleCycleAgainstOneVsTwo $rootCycle
    ): array {
        $games = [];
        $cycle = $rootCycle;
        while($cycle !== null) {
            foreach ($cycle->getAllCyclePartGames() as $game) {
                $games[] = $game;
            }
            $cycle = $cycle->getNext();
        }
        return $games;
    }

    protected function getAgainstTwoVsTwoGames(
        ScheduleCycleAgainstTwoVsTwo $rootCycle
    ): array {
        $games = [];
        $cycle = $rootCycle;
        while($cycle !== null) {
            foreach ($cycle->getAllCyclePartGames() as $game) {
                $games[] = $game;
            }
            $cycle = $cycle->getNext();
        }
        return $games;
    }
}