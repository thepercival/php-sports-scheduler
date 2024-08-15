<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstVariant;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Place;
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

    protected function duoPlaceNrCounterMapIsBalanced(DuoPlaceNrCounterMap $duoPlaceNrCounterMap): bool
    {
        return $duoPlaceNrCounterMap->calculateReport()->getAmountDifference() === 0;
    }

    abstract public function addGame(AgainstGame $game, Sport $sport): void;
}
