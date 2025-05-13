<?php

namespace SportsScheduler\Schedules\CycleCreators\Helpers;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class Solutions4N
{
    /**
     * @var array<int, list<array<string, list<int>>>> $map
     */
    private static array $map = [
        4 => [
            [ AgainstSide::Home->value => [ 1, 2], AgainstSide::Away->value => [ 3, 0] ]
        ],
        8 => [
            [ AgainstSide::Home->value => [ 2, 3], AgainstSide::Away->value => [ 4, 6] ],
            [ AgainstSide::Home->value => [ 5, 1], AgainstSide::Away->value => [ 7, 0] ]
        ],
        12 => [
            [ AgainstSide::Home->value => [ 8, 9], AgainstSide::Away->value => [ 4, 6] ],
            [ AgainstSide::Home->value => [ 2, 5], AgainstSide::Away->value => [10, 3] ],
            [ AgainstSide::Home->value => [ 7, 1], AgainstSide::Away->value => [11, 0] ]
        ],
        16 => [
            [ AgainstSide::Home->value => [ 6, 7], AgainstSide::Away->value => [ 1, 5] ],
            [ AgainstSide::Home->value => [10,12], AgainstSide::Away->value => [ 9, 2] ],
            [ AgainstSide::Home->value => [11,14], AgainstSide::Away->value => [ 3, 8] ],
            [ AgainstSide::Home->value => [13, 4], AgainstSide::Away->value => [15, 0] ]
        ],
        20 => [
            [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [ 9,15] ],
            [ AgainstSide::Home->value => [14,16], AgainstSide::Away->value => [12, 7] ],
            [ AgainstSide::Home->value => [ 2, 5], AgainstSide::Away->value => [10, 1] ],
            [ AgainstSide::Home->value => [ 4, 8], AgainstSide::Away->value => [ 3,11] ],
            [ AgainstSide::Home->value => [ 6,13], AgainstSide::Away->value => [19, 0] ],
        ],
        24 => [
            [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [ 3,13] ],
            [ AgainstSide::Home->value => [20,22], AgainstSide::Away->value => [ 9, 6] ],
            [ AgainstSide::Home->value => [15,19], AgainstSide::Away->value => [ 8,16] ],
            [ AgainstSide::Home->value => [ 5,10], AgainstSide::Away->value => [ 2,11] ],
            [ AgainstSide::Home->value => [21, 4], AgainstSide::Away->value => [ 0,23] ],
            [ AgainstSide::Home->value => [ 7,14], AgainstSide::Away->value => [12, 1] ],
        ],
        28 => [
            [ AgainstSide::Home->value => [ 9,10], AgainstSide::Away->value => [25, 5] ],
            [ AgainstSide::Home->value => [21,23], AgainstSide::Away->value => [12, 6] ],
            [ AgainstSide::Home->value => [ 8,11], AgainstSide::Away->value => [15, 3] ],
            [ AgainstSide::Home->value => [16,20], AgainstSide::Away->value => [17, 7] ],
            [ AgainstSide::Home->value => [19,24], AgainstSide::Away->value => [18,26] ],
            [ AgainstSide::Home->value => [22, 4], AgainstSide::Away->value => [14, 1] ],
            [ AgainstSide::Home->value => [ 2,13], AgainstSide::Away->value => [27, 0] ]
        ],
        32 => [
            [ AgainstSide::Home->value => [11,12], AgainstSide::Away->value => [13,23] ],
            [ AgainstSide::Home->value => [ 7, 9], AgainstSide::Away->value => [30,25] ],
            [ AgainstSide::Home->value => [24,27], AgainstSide::Away->value => [29,20] ],
            [ AgainstSide::Home->value => [22,26], AgainstSide::Away->value => [17, 1] ],
            [ AgainstSide::Home->value => [ 4,10], AgainstSide::Away->value => [21,28] ],
            [ AgainstSide::Home->value => [ 6,14], AgainstSide::Away->value => [18, 5] ],
            [ AgainstSide::Home->value => [ 8,19], AgainstSide::Away->value => [16, 2] ],
            [ AgainstSide::Home->value => [ 3,15], AgainstSide::Away->value => [31, 0] ],
        ],
        36 => [
            [ AgainstSide::Home->value => [26,27], AgainstSide::Away->value => [10,12] ],
            [ AgainstSide::Home->value => [29,32], AgainstSide::Away->value => [ 2,18] ],
            [ AgainstSide::Home->value => [ 9,13], AgainstSide::Away->value => [16, 4] ],
            [ AgainstSide::Home->value => [ 6,11], AgainstSide::Away->value => [31, 7] ],
            [ AgainstSide::Home->value => [28,34], AgainstSide::Away->value => [ 0,35] ],
            [ AgainstSide::Home->value => [23,30], AgainstSide::Away->value => [ 1,19] ],
            [ AgainstSide::Home->value => [14,22], AgainstSide::Away->value => [20, 5] ],
            [ AgainstSide::Home->value => [24,33], AgainstSide::Away->value => [21, 8] ],
            [ AgainstSide::Home->value => [15,25], AgainstSide::Away->value => [ 3,17] ],
        ],
        40 => [
            [ AgainstSide::Home->value => [35,36], AgainstSide::Away->value => [ 5,22] ],
            [ AgainstSide::Home->value => [32,34], AgainstSide::Away->value => [37,30] ],
            [ AgainstSide::Home->value => [ 9,12], AgainstSide::Away->value => [19, 3] ],
            [ AgainstSide::Home->value => [27,31], AgainstSide::Away->value => [17, 7] ],
            [ AgainstSide::Home->value => [28,33], AgainstSide::Away->value => [16,10] ],
            [ AgainstSide::Home->value => [ 6,14], AgainstSide::Away->value => [25,13] ],
            [ AgainstSide::Home->value => [29,38], AgainstSide::Away->value => [ 1,21] ],
            [ AgainstSide::Home->value => [15,26], AgainstSide::Away->value => [ 2,20] ],
            [ AgainstSide::Home->value => [11,24], AgainstSide::Away->value => [23, 8] ],
            [ AgainstSide::Home->value => [ 4,18], AgainstSide::Away->value => [ 0,39] ],
        ],
        44 => [
            [ AgainstSide::Home->value => [32,33], AgainstSide::Away->value => [ 8,25] ],
            [ AgainstSide::Home->value => [34,36], AgainstSide::Away->value => [ 2,22] ],
            [ AgainstSide::Home->value => [12,15], AgainstSide::Away->value => [ 9,41] ],
            [ AgainstSide::Home->value => [35,39], AgainstSide::Away->value => [31,40] ],
            [ AgainstSide::Home->value => [37,42], AgainstSide::Away->value => [ 4,20] ],
            [ AgainstSide::Home->value => [10,16], AgainstSide::Away->value => [23, 1] ],
            [ AgainstSide::Home->value => [ 7,14], AgainstSide::Away->value => [27,13] ],
            [ AgainstSide::Home->value => [30,38], AgainstSide::Away->value => [11,26] ],
            [ AgainstSide::Home->value => [18,28], AgainstSide::Away->value => [ 0,43] ],
            [ AgainstSide::Home->value => [17,29], AgainstSide::Away->value => [ 6,19] ],
            [ AgainstSide::Home->value => [ 3,21], AgainstSide::Away->value => [ 5,24] ],
        ]
    ];

    /**
     * @param list<int> $placeNrs
     * @return list<TwoVsTwoHomeAway>
     */
    public static function create(array $placeNrs): array {
        $nrOfPlaces = count($placeNrs);
        if( !array_key_exists($nrOfPlaces, self::$map) ) {
            throw new \Exception('implement solution');
        }
        return array_map(function(array $homeAwayAsNumbers) use ($placeNrs): TwoVsTwoHomeAway {
            return new TwoVsTwoHomeAway(
                new DuoPlaceNr(
                    $placeNrs[$homeAwayAsNumbers[AgainstSide::Home->value][0]-1],
                    $placeNrs[$homeAwayAsNumbers[AgainstSide::Home->value][1]-1],
                ),
                new DuoPlaceNr(
                    $placeNrs[$homeAwayAsNumbers[AgainstSide::Away->value][0]-1],
                    $placeNrs[$homeAwayAsNumbers[AgainstSide::Away->value][1]-1],
                )
            );
        }, self::$map[$nrOfPlaces] );
    }
}
