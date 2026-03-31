<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\Validators;

use SportsHelpers\Against\AgainstSide;
use SportsScheduler\Combinations\Validators;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

final class AgainstValidator extends ValidatorAbstract
{
    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        parent::__construct($poule, $sport);
    }

    #[\Override]
    public function addGame(AgainstGame $game): void
    {
        if ($game->getSport() !== $this->sport) {
            return;
        }

        foreach( $game->getSidePlaces(AgainstSide::Home) as $homeGamePlace ) {

            $placeNrCounterMap = $this->placeNrCounterMaps[$homeGamePlace->getPlace()->getPlaceNr()];
//            if ($placeCounterMap === null ) {
//                throw new \Exception('placeCounter not found');
//            }
            foreach( $game->getSidePlaces(AgainstSide::Away) as $awayGamePlace ) {
                $placeNrCounterMap = $placeNrCounterMap->addPlaceNr($awayGamePlace->getPlace()->getPlaceNr());
            }
            $this->placeNrCounterMaps[$homeGamePlace->getPlace()->getPlaceNr()] = $placeNrCounterMap;
        }

        foreach( $game->getSidePlaces(AgainstSide::Away) as $awayGamePlace ) {

            $placeNrCounterMap = $this->placeNrCounterMaps[$awayGamePlace->getPlace()->getPlaceNr()];
//            if ($placeNrCounterMap === null ) {
//                throw new \Exception('placeCounter not found');
//            }
            foreach( $game->getSidePlaces(AgainstSide::Home) as $homeGamePlace ) {
                $placeNrCounterMap = $placeNrCounterMap->addPlaceNr($homeGamePlace->getPlace()->getPlaceNr());
            }
            $this->placeNrCounterMaps[$awayGamePlace->getPlace()->getPlaceNr()] = $placeNrCounterMap;
        }

//        $homeAway = new HomeAway(
//            new PlaceNrCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Home)->toArray() )
//            ) ),
//            new PlaceNrCombination( array_values(
//                array_map( function(AgainstGamePlace $againstGamePlace): Place {
//                    return $againstGamePlace->getPlace();
//                }, $game->getSidePlaces(Side::Away)->toArray() )
//            ) )
//        );
//        // WHEN CHECKING AGAINST, JUST CHECK 1 VS 1 EVEN IF 2 VS 2 ganes
//        foreach( $homeAway->getAgainstPlaceNrCombinations() as $againstPlaceNrCombination) {
//            $againstPlaceNrCombination->
//
//        }
//        $homePlaceNrCombination = $this->getPlaceNrCombination($game, Side::Home);
//        $awayPlaceNrCombination = $this->getPlaceNrCombination($game, Side::Away);
//        if (isset($this->counters[$homePlaceNrCombination->getIndex()])) {
//            $this->counters[$homePlaceNrCombination->getIndex()]->addCombination($awayPlaceNrCombination);
//        }
//        if (isset($this->counters[$awayPlaceNrCombination->getIndex()])) {
//            $this->counters[$awayPlaceNrCombination->getIndex()]->addCombination($homePlaceNrCombination);
//        }
    }

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
