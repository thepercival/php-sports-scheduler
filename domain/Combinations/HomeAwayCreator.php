<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Poule;

abstract class HomeAwayCreator
{
    private bool $swap = false;
    // protected VariantWithPoule $variantWithPoule;

    public function __construct(/*protected Poule $poule, AgainstH2h|AgainstGpp $sportVariant*/)
    {
        // $this->variantWithPoule = new VariantWithPoule($sportVariant, count($poule->getPlaces()));
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
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
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    private function swapHomeAways(array $homeAways): array
    {
        $swapped = [];
        foreach ($homeAways as $homeAway) {
            array_push($swapped, $homeAway->swap());
        }
        return $swapped;
    }
}
