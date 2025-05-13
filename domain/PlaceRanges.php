<?php

declare(strict_types=1);

namespace SportsScheduler;

use SportsHelpers\PlaceRanges as PlaceRangesBase;

readonly class PlaceRanges extends PlaceRangesBase
{
    public const int MaxNrOfPlacesPerPouleSmall = 20;
    public const int MaxNrOfPlacesPerPouleLarge = 12;
    public const int MaxNrOfPlacesPerRoundSmall = 40;
    public const int MaxNrOfPlacesPerRoundLarge = 128;

    public function __construct(int $minNrOfPlacesPerPoule)
    {
        parent::__construct(
            $minNrOfPlacesPerPoule,
            self::MaxNrOfPlacesPerPouleSmall,
            self::MaxNrOfPlacesPerPouleLarge,
            $minNrOfPlacesPerPoule,
            self::MaxNrOfPlacesPerRoundSmall,
            self::MaxNrOfPlacesPerRoundLarge,
        );
    }
}
