<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\HomeAwayCreators;

use SportsHelpers\Sport\Variant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\HomeAway;

abstract class HomeAwayCreatorAbstract
{
    private bool $swap = false;
    // protected VariantWithPoule $variantWithPoule;

    public function __construct(/*protected Poule $poule, AgainstH2h|AgainstGpp $sportVariant*/)
    {
        // $this->variantWithPoule = new VariantWithPoule($sportVariant, count($poule->getPlaces()));
    }

    /**
     * @param HomeAway $homeAways
     * @return HomeAway
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
     * @param HomeAway $homeAways
     * @return HomeAway
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
