<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersPerAmountReport;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

abstract class ValidatorAbstract
{

    public function __construct()
    {
//        $sportVariant = $this->sport->createVariant();
//        if (!($sportVariant instanceof AgainstSportVariant)) {
//            throw new \Exception('only against-sports', E_ERROR);
//        }
//        $this->sportVariant = $sportVariant;
    }



//    public function getPlaceCombination(AgainstGame $game, AgainstSide $side): PlaceCombination
//    {
//        $poulePlaces = array_values( array_map(function (AgainstGamePlace $gamePlace): Place {
//            return $gamePlace->getPlace();
//        }, $game->getSidePlaces($side)->toArray() ) );
//        return new PlaceCombination($poulePlaces);
//    }

    public function addGames(Planning $planning, Poule $poule, Sport $sport): void
    {
        foreach ($planning->getAgainstGamesForPoule($poule) as $game) {
            $this->addGame($game, $sport);
        }
    }

    protected function duoPlaceNrCounterMapIsBalanced(AgainstNrCounterMap|TogetherNrCounterMap|WithNrCounterMap $duoPlaceNrCounterMap): bool
    {
        $report = new DuoPlaceNrCountersPerAmountReport($duoPlaceNrCounterMap);
        return $report->range->getAmountDifference() === 0;
    }

    abstract public function addGame(AgainstGame $game, Sport $sport): void;
}
