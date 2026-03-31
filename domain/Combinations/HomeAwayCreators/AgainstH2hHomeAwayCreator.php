<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayCreators;

use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Place;
use SportsPlanning\SportVariant\AgainstH2hWithNrOfPlaces;

final class AgainstH2hHomeAwayCreator extends HomeAwayCreatorAbstract
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param AgainstH2hWithNrOfPlaces $againstH2hWithNrOfPlaces
     * @return list<HomeAway>
     */
    public function createForOneH2h(AgainstH2hWithNrOfPlaces $againstH2hWithNrOfPlaces): array
    {
        $homeAways = [];

        $nrOfPlaces = $againstH2hWithNrOfPlaces->getNrOfPlaces();
        /** @var list<int|null> $schedulePlaceNrs */
        $schedulePlaceNrs = [];
        for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
            $schedulePlaceNrs[] = $placeNr;
        }
        if ($nrOfPlaces % 2 != 0) {
            array_push($schedulePlaceNrs, null);
        }
        /** @var list<int|null> $away */
        $away = array_splice($schedulePlaceNrs, (int)(count($schedulePlaceNrs) / 2));
        /** @var list<int|null> $home */
        $home = $schedulePlaceNrs;
        for ($gameRoundNr = 0; $gameRoundNr < count($home) + count($away) - 1; $gameRoundNr++) {
            for ($gameNr = 0; $gameNr < count($home); $gameNr++) {
                /** @var int|null $homePlaceNr */
                $homePlaceNr = $home[$gameNr];
                /** @var int|null $awayPlaceNr */
                $awayPlaceNr = $away[$gameNr];
                if ($homePlaceNr === null || $awayPlaceNr === null) {
                    continue;
                }
                $homeAways[] = $this->createHomeAway($homePlaceNr, $awayPlaceNr);
            }
            if (count($home) + count($away) - 1 > 2) {
                $removedSecond = array_splice($home, 1, 1);
                array_unshift($away, array_shift($removedSecond));
                array_push($home, array_pop($away));
            }
        }

        return $this->swap($homeAways);
    }

    protected function createHomeAway(int $homePlaceNr, int $awayPlaceNr): HomeAway
    {
        if ($this->shouldSwap($homePlaceNr, $awayPlaceNr)) {
            return new HomeAway(new PlaceNrCombination([$awayPlaceNr]), new PlaceNrCombination([$homePlaceNr]));
        }
        return new HomeAway(new PlaceNrCombination([$homePlaceNr]), new PlaceNrCombination([$awayPlaceNr]));
    }

    protected function shouldSwap(int $homePlaceNr, int $awayPlaceNr): bool
    {
        $even = (($homePlaceNr + $awayPlaceNr) % 2) === 0;
        if ($even && $homePlaceNr < $awayPlaceNr) {
            return true;
        }
        if (!$even && $homePlaceNr > $awayPlaceNr) {
            return true;
        }
        return false;
    }
}
