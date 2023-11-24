<?php

namespace SportsScheduler\Schedule;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;

class SportVariantWithNr
{
    public function __construct(
        public readonly int $number,
        public readonly Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant){

    }
}