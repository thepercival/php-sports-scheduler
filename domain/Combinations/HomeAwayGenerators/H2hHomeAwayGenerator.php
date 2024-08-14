<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayGenerators;

use SportsHelpers\SportRange;
use SportsPlanning\HomeAways\OneVsOneHomeAway;

final class H2hHomeAwayGenerator
{
    private bool $swap = false;

    /**
     * @param int $nrOfPlaces
     * @return list<OneVsOneHomeAway>
     */
    public function createForOneH2h(int $nrOfPlaces): array
    {
        $homeAways = [];

        /** @var list<int|null> $schedulePlaceNrs */
        $schedulePlaceNrs = (new SportRange(1, $nrOfPlaces))->toArray();

        if ($nrOfPlaces % 2 !== 0) {
            $schedulePlaceNrs[] = null;
        }
        $away = array_splice($schedulePlaceNrs, (int)(count($schedulePlaceNrs) / 2));
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
                $home[] = array_pop($away);
            }
        }

        return $this->swap($homeAways);
    }

    protected function createHomeAway(int $homePlaceNr, int $awayPlaceNr): OneVsOneHomeAway
    {
        $homeAway = new OneVsOneHomeAway($homePlaceNr, $awayPlaceNr);
        if ($this->shouldSwap($homePlaceNr, $awayPlaceNr)) {
            return $homeAway->swap();
        }
        return $homeAway;
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

    /**
     * @param list<OneVsOneHomeAway> $homeAways
     * @return list<OneVsOneHomeAway>
     */
    protected function swap(array $homeAways): array
    {
        if ($this->swap === true) {
            $homeAways = $this->swapHomeAways($homeAways);
        }
        $this->swap = !$this->swap;
        return $homeAways;
    }

    /**
     * @param list<OneVsOneHomeAway> $homeAways
     * @return list<OneVsOneHomeAway>
     */
    private function swapHomeAways(array $homeAways): array
    {
        $swapped = [];
        foreach ($homeAways as $homeAway) {
            $swapped[] = $homeAway->swap();
        }
        return $swapped;
    }
}
