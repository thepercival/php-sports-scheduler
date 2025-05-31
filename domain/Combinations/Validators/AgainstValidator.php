<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning;

class AgainstValidator extends ValidatorAbstract
{
    protected AgainstNrCounterMap $againstNrCounterMap;

    public function __construct(int $nrOfPlaces)
    {
        parent::__construct();
        $this->againstNrCounterMap = new AgainstNrCounterMap($nrOfPlaces);
    }

    public function balanced(): bool
    {
        return $this->duoPlaceNrCounterMapIsBalanced($this->againstNrCounterMap);
    }

    public function addGames(Planning $planning): void
    {
        foreach ($planning->poules as $poule) {
            foreach ($poule->getAgainstGames() as $againstGame) {
                $this->addGame($planning, $againstGame);
            }
        }
    }

    public function addGame(Planning $planning, AgainstGame $game): void
    {
        $this->addHomeAway($planning->convertAgainstGameToHomeAway($game));
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->againstNrCounterMap->addHomeAway($homeAway);
    }

    public function cloneAgainstNrCounterMap(): AgainstNrCounterMap {
        return clone $this->againstNrCounterMap;

    }

//        $homeAway = new HomeAway(
//            new PlaceCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Home)->toArray() )
//            ) ),
//            new PlaceCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Away)->toArray() )
//            ) )
//        );
//        // WHEN CHECKING AGAINST, JUST CHECK 1 VS 1 EVEN IF 2 VS 2 ganes
//        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
//            $againstPlaceCombination->
//
//        }
//        $homePlaceCombination = $this->getPlaceCombination($game, Side::Home);
//        $awayPlaceCombination = $this->getPlaceCombination($game, Side::Away);
//        if (isset($this->counters[$homePlaceCombination->getIndex()])) {
//            $this->counters[$homePlaceCombination->getIndex()]->addCombination($awayPlaceCombination);
//        }
//        if (isset($this->counters[$awayPlaceCombination->getIndex()])) {
//            $this->counters[$awayPlaceCombination->getIndex()]->addCombination($homePlaceCombination);
//        }
//    }

//    public function balanced(): bool
//    {
//        foreach ($this->counters as $counter) {
//            if (!$counter->balanced()) {
//                return false;
//            }
//        }
//        return true;
//    }

//    public function totalCount(): int
//    {
//        $totalCount = 0;
//        foreach ($this->counters as $counter) {
//            $totalCount += $counter->totalCount();
//        }
//        return $totalCount;
//    }

//    public function __toString(): string
//    {
//        $header = ' all against-counters: ' . $this->totalCount() . 'x' . PHP_EOL;
//        $lines = '';
//        foreach ($this->counters as $counter) {
//            $lines .= $counter;
//        }
//
//        return $header . $lines;
//    }
}
