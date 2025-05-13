<?php

namespace SportsScheduler\Schedules\CycleCreators\Helpers;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class Solutions4NPlus2
{
    /**
     * @var array<int, array<int, list<array<string, list<int>>>>> $map
     */
    private static array $map = [
        6 => [
            1 => [
                [ AgainstSide::Home->value => [ 4, 5], AgainstSide::Away->value => [ 6, 2] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [ 2, 3], AgainstSide::Away->value => [ 1, 6] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [ 4, 6], AgainstSide::Away->value => [ 5, 1] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [ 3, 6], AgainstSide::Away->value => [ 2, 4] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [ 1, 4], AgainstSide::Away->value => [ 5, 2] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [ 6, 5], AgainstSide::Away->value => [ 3, 1] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [ 1, 2], AgainstSide::Away->value => [ 4, 3] ],
            ],
        ],
        10 => [
            1 => [
                [ AgainstSide::Home->value => [4,6], AgainstSide::Away->value => [5,10] ],
                [ AgainstSide::Home->value => [2,3], AgainstSide::Away->value => [7,9] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [10,9], AgainstSide::Away->value => [8,6] ],
                [ AgainstSide::Home->value => [3,1], AgainstSide::Away->value => [2,4] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [9,5], AgainstSide::Away->value => [10,2] ],
                [ AgainstSide::Home->value => [8,1], AgainstSide::Away->value => [4,7] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [2,8], AgainstSide::Away->value => [3,10] ],
                [ AgainstSide::Home->value => [5,4], AgainstSide::Away->value => [1,6] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [6,9], AgainstSide::Away->value => [1,7] ],
                [ AgainstSide::Home->value => [2,5], AgainstSide::Away->value => [8,4] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [1,4], AgainstSide::Away->value => [2,9] ],
                [ AgainstSide::Home->value => [3,6], AgainstSide::Away->value => [10,7] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [5,1], AgainstSide::Away->value => [9,3] ],
                [ AgainstSide::Home->value => [7,8], AgainstSide::Away->value => [6,2] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [4,10], AgainstSide::Away->value => [7,2] ],
                [ AgainstSide::Home->value => [6,5], AgainstSide::Away->value => [9,8] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [5,7], AgainstSide::Away->value => [10,1] ],
                [ AgainstSide::Home->value => [4,9], AgainstSide::Away->value => [8,3] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [7,3], AgainstSide::Away->value => [5,8] ],
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [6,10] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [3,4] ],
                [ AgainstSide::Home->value => [10,8], AgainstSide::Away->value => [9,1] ],
            ],
        ],
        14 => [
            1 => [
                [ AgainstSide::Home->value => [6,10], AgainstSide::Away->value => [9,1] ],
                [ AgainstSide::Home->value => [4,3], AgainstSide::Away->value => [12,2] ],
                [ AgainstSide::Home->value => [13,7], AgainstSide::Away->value => [5,11] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [14,13], AgainstSide::Away->value => [8,12] ],
                [ AgainstSide::Home->value => [11,4], AgainstSide::Away->value => [3,1] ],
                [ AgainstSide::Home->value => [5,9], AgainstSide::Away->value => [7,2] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [5,14], AgainstSide::Away->value => [13,10] ],
                [ AgainstSide::Home->value => [7,11], AgainstSide::Away->value => [4,12] ],
                [ AgainstSide::Home->value => [2,3], AgainstSide::Away->value => [9,6] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [4,8], AgainstSide::Away->value => [9,11] ],
                [ AgainstSide::Home->value => [13,1], AgainstSide::Away->value => [2,10] ],
                [ AgainstSide::Home->value => [6,12], AgainstSide::Away->value => [3,5] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [14,12], AgainstSide::Away->value => [2,5] ],
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [8,9] ],
                [ AgainstSide::Home->value => [10,1], AgainstSide::Away->value => [11,13] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [13,3], AgainstSide::Away->value => [1,7] ],
                [ AgainstSide::Home->value => [11,10], AgainstSide::Away->value => [2,14] ],
                [ AgainstSide::Home->value => [8,5], AgainstSide::Away->value => [6,4] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [10,12], AgainstSide::Away->value => [6,8] ],
                [ AgainstSide::Home->value => [1,4], AgainstSide::Away->value => [5,7] ],
                [ AgainstSide::Home->value => [9,13], AgainstSide::Away->value => [14,3] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [4,5], AgainstSide::Away->value => [14,9] ],
                [ AgainstSide::Home->value => [2,13], AgainstSide::Away->value => [6,11] ],
                [ AgainstSide::Home->value => [7,12], AgainstSide::Away->value => [8,1] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [10,14], AgainstSide::Away->value => [7,4] ],
                [ AgainstSide::Home->value => [11,8], AgainstSide::Away->value => [13,5] ],
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [12,3] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [7,8], AgainstSide::Away->value => [10,3] ],
                [ AgainstSide::Home->value => [5,6], AgainstSide::Away->value => [12,11] ],
                [ AgainstSide::Home->value => [14,1], AgainstSide::Away->value => [9,2] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [12,1], AgainstSide::Away->value => [11,14] ],
                [ AgainstSide::Home->value => [4,2], AgainstSide::Away->value => [10,8] ],
                [ AgainstSide::Home->value => [3,7], AgainstSide::Away->value => [13,6] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [3,9], AgainstSide::Away->value => [8,13] ],
                [ AgainstSide::Home->value => [1,11], AgainstSide::Away->value => [14,6] ],
                [ AgainstSide::Home->value => [4,10], AgainstSide::Away->value => [5,12] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [14,4], AgainstSide::Away->value => [1,6] ],
                [ AgainstSide::Home->value => [10,5], AgainstSide::Away->value => [3,8] ],
                [ AgainstSide::Home->value => [11,2], AgainstSide::Away->value => [7,9] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [2,6], AgainstSide::Away->value => [13,4] ],
                [ AgainstSide::Home->value => [8,14], AgainstSide::Away->value => [11,3] ],
                [ AgainstSide::Home->value => [12,9], AgainstSide::Away->value => [10,7] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [12,13], AgainstSide::Away->value => [4,9] ],
                [ AgainstSide::Home->value => [3,6], AgainstSide::Away->value => [14,7] ],
                [ AgainstSide::Home->value => [2,8], AgainstSide::Away->value => [1,5] ],
            ],
        ],
        18 => [
            1 => [
                [ AgainstSide::Home->value => [1,11], AgainstSide::Away->value => [8,9] ],
                [ AgainstSide::Home->value => [17,14], AgainstSide::Away->value => [13,2] ],
                [ AgainstSide::Home->value => [12,3], AgainstSide::Away->value => [4,5] ],
                [ AgainstSide::Home->value => [18,6], AgainstSide::Away->value => [16,10] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [5,2], AgainstSide::Away->value => [6,14] ],
                [ AgainstSide::Home->value => [9,15], AgainstSide::Away->value => [12,10] ],
                [ AgainstSide::Home->value => [18,17], AgainstSide::Away->value => [8,7] ],
                [ AgainstSide::Home->value => [13,3], AgainstSide::Away->value => [1,4] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [13,4], AgainstSide::Away->value => [17,7] ],
                [ AgainstSide::Home->value => [16,1], AgainstSide::Away->value => [18,14] ],
                [ AgainstSide::Home->value => [10,6], AgainstSide::Away->value => [2,9] ],
                [ AgainstSide::Home->value => [11,8], AgainstSide::Away->value => [12,15] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [5,12], AgainstSide::Away->value => [15,18] ],
                [ AgainstSide::Home->value => [3,9], AgainstSide::Away->value => [8,6] ],
                [ AgainstSide::Home->value => [16,11], AgainstSide::Away->value => [7,13] ],
                [ AgainstSide::Home->value => [2,4], AgainstSide::Away->value => [10,1] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [17,10], AgainstSide::Away->value => [9,1] ],
                [ AgainstSide::Home->value => [11,4], AgainstSide::Away->value => [16,2] ],
                [ AgainstSide::Home->value => [15,8], AgainstSide::Away->value => [5,6] ],
                [ AgainstSide::Home->value => [12,14], AgainstSide::Away->value => [3,7] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [3,5], AgainstSide::Away->value => [13,15] ],
                [ AgainstSide::Home->value => [10,8], AgainstSide::Away->value => [7,1] ],
                [ AgainstSide::Home->value => [4,14], AgainstSide::Away->value => [9,11] ],
                [ AgainstSide::Home->value => [2,17], AgainstSide::Away->value => [16,18] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [14,1], AgainstSide::Away->value => [16,12] ],
                [ AgainstSide::Home->value => [11,13], AgainstSide::Away->value => [5,18] ],
                [ AgainstSide::Home->value => [6,2], AgainstSide::Away->value => [17,3] ],
                [ AgainstSide::Home->value => [7,15], AgainstSide::Away->value => [4,9] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [3,15], AgainstSide::Away->value => [18,8] ],
                [ AgainstSide::Home->value => [4,7], AgainstSide::Away->value => [12,6] ],
                [ AgainstSide::Home->value => [13,1], AgainstSide::Away->value => [11,10] ],
                [ AgainstSide::Home->value => [16,17], AgainstSide::Away->value => [14,5] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [12,7], AgainstSide::Away->value => [11,2] ],
                [ AgainstSide::Home->value => [14,16], AgainstSide::Away->value => [1,5] ],
                [ AgainstSide::Home->value => [3,18], AgainstSide::Away->value => [9,17] ],
                [ AgainstSide::Home->value => [10,13], AgainstSide::Away->value => [6,15] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [6,13], AgainstSide::Away->value => [14,3] ],
                [ AgainstSide::Home->value => [7,11], AgainstSide::Away->value => [17,8] ],
                [ AgainstSide::Home->value => [2,1], AgainstSide::Away->value => [15,5] ],
                [ AgainstSide::Home->value => [9,16], AgainstSide::Away->value => [4,12] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [16,8], AgainstSide::Away->value => [2,12] ],
                [ AgainstSide::Home->value => [10,18], AgainstSide::Away->value => [3,4] ],
                [ AgainstSide::Home->value => [9,7], AgainstSide::Away->value => [13,14] ],
                [ AgainstSide::Home->value => [5,11], AgainstSide::Away->value => [1,6] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [11,17], AgainstSide::Away->value => [10,4] ],
                [ AgainstSide::Home->value => [13,5], AgainstSide::Away->value => [9,12] ],
                [ AgainstSide::Home->value => [1,15], AgainstSide::Away->value => [3,16] ],
                [ AgainstSide::Home->value => [8,2], AgainstSide::Away->value => [18,7] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [9,13], AgainstSide::Away->value => [15,16] ],
                [ AgainstSide::Home->value => [18,1], AgainstSide::Away->value => [6,11] ],
                [ AgainstSide::Home->value => [14,2], AgainstSide::Away->value => [8,4] ],
                [ AgainstSide::Home->value => [17,12], AgainstSide::Away->value => [3,10] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [8,5], AgainstSide::Away->value => [4,17] ],
                [ AgainstSide::Home->value => [2,10], AgainstSide::Away->value => [13,12] ],
                [ AgainstSide::Home->value => [7,16], AgainstSide::Away->value => [6,3] ],
                [ AgainstSide::Home->value => [11,18], AgainstSide::Away->value => [15,14] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [11,3], AgainstSide::Away->value => [14,10] ],
                [ AgainstSide::Home->value => [16,4], AgainstSide::Away->value => [15,17] ],
                [ AgainstSide::Home->value => [12,18], AgainstSide::Away->value => [1,8] ],
                [ AgainstSide::Home->value => [6,9], AgainstSide::Away->value => [7,5] ],
            ],
            16 => [
                [ AgainstSide::Home->value => [4,6], AgainstSide::Away->value => [18,13] ],
                [ AgainstSide::Home->value => [8,12], AgainstSide::Away->value => [14,9] ],
                [ AgainstSide::Home->value => [16,5], AgainstSide::Away->value => [10,7] ],
                [ AgainstSide::Home->value => [15,2], AgainstSide::Away->value => [17,1] ],
            ],
            17 => [
                [ AgainstSide::Home->value => [10,5], AgainstSide::Away->value => [8,14] ],
                [ AgainstSide::Home->value => [15,11], AgainstSide::Away->value => [2,3] ],
                [ AgainstSide::Home->value => [1,12], AgainstSide::Away->value => [13,17] ],
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [18,4] ],
            ],
            18 => [
                [ AgainstSide::Home->value => [1,3], AgainstSide::Away->value => [7,2] ],
                [ AgainstSide::Home->value => [6,16], AgainstSide::Away->value => [8,13] ],
                [ AgainstSide::Home->value => [15,4], AgainstSide::Away->value => [14,11] ],
                [ AgainstSide::Home->value => [10,9], AgainstSide::Away->value => [5,17] ],
            ],
            19 => [
                [ AgainstSide::Home->value => [17,6], AgainstSide::Away->value => [12,11] ],
                [ AgainstSide::Home->value => [7,14], AgainstSide::Away->value => [10,15] ],
                [ AgainstSide::Home->value => [5,9], AgainstSide::Away->value => [18,2] ],
                [ AgainstSide::Home->value => [8,3], AgainstSide::Away->value => [13,16] ],
            ],
        ],
        22 => [
            1 => [
                [ AgainstSide::Home->value => [4,8], AgainstSide::Away->value => [20,11] ],
                [ AgainstSide::Home->value => [10,2], AgainstSide::Away->value => [7,18] ],
                [ AgainstSide::Home->value => [19,17], AgainstSide::Away->value => [3,9] ],
                [ AgainstSide::Home->value => [16,1], AgainstSide::Away->value => [15,13] ],
                [ AgainstSide::Home->value => [5,12], AgainstSide::Away->value => [6,14] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [7,9], AgainstSide::Away->value => [5,16] ],
                [ AgainstSide::Home->value => [12,17], AgainstSide::Away->value => [1,11] ],
                [ AgainstSide::Home->value => [10,20], AgainstSide::Away->value => [15,6] ],
                [ AgainstSide::Home->value => [22,14], AgainstSide::Away->value => [21,2] ],
                [ AgainstSide::Home->value => [3,8], AgainstSide::Away->value => [19,13] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [12,20], AgainstSide::Away->value => [13,10] ],
                [ AgainstSide::Home->value => [21,7], AgainstSide::Away->value => [15,3] ],
                [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [8,14] ],
                [ AgainstSide::Home->value => [6,11], AgainstSide::Away->value => [5,19] ],
                [ AgainstSide::Home->value => [9,16], AgainstSide::Away->value => [2,4] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [9,19], AgainstSide::Away->value => [15,8] ],
                [ AgainstSide::Home->value => [1,20], AgainstSide::Away->value => [2,17] ],
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [12,10] ],
                [ AgainstSide::Home->value => [16,4], AgainstSide::Away->value => [18,22] ],
                [ AgainstSide::Home->value => [11,13], AgainstSide::Away->value => [3,14] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [4,21], AgainstSide::Away->value => [18,3] ],
                [ AgainstSide::Home->value => [7,22], AgainstSide::Away->value => [13,14] ],
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [16,11] ],
                [ AgainstSide::Home->value => [17,15], AgainstSide::Away->value => [10,6] ],
                [ AgainstSide::Home->value => [20,5], AgainstSide::Away->value => [8,19] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [11,22], AgainstSide::Away->value => [8,20] ],
                [ AgainstSide::Home->value => [6,5], AgainstSide::Away->value => [16,2] ],
                [ AgainstSide::Home->value => [19,10], AgainstSide::Away->value => [12,21] ],
                [ AgainstSide::Home->value => [14,4], AgainstSide::Away->value => [13,1] ],
                [ AgainstSide::Home->value => [15,7], AgainstSide::Away->value => [9,18] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [17,5], AgainstSide::Away->value => [15,4] ],
                [ AgainstSide::Home->value => [11,12], AgainstSide::Away->value => [21,18] ],
                [ AgainstSide::Home->value => [8,13], AgainstSide::Away->value => [22,2] ],
                [ AgainstSide::Home->value => [9,20], AgainstSide::Away->value => [3,7] ],
                [ AgainstSide::Home->value => [10,1], AgainstSide::Away->value => [14,16] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [18,12], AgainstSide::Away->value => [2,19] ],
                [ AgainstSide::Home->value => [3,13], AgainstSide::Away->value => [9,1] ],
                [ AgainstSide::Home->value => [21,14], AgainstSide::Away->value => [10,11] ],
                [ AgainstSide::Home->value => [8,6], AgainstSide::Away->value => [16,15] ],
                [ AgainstSide::Home->value => [17,7], AgainstSide::Away->value => [20,22] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [22,9], AgainstSide::Away->value => [14,11] ],
                [ AgainstSide::Home->value => [19,16], AgainstSide::Away->value => [10,8] ],
                [ AgainstSide::Home->value => [18,20], AgainstSide::Away->value => [5,15] ],
                [ AgainstSide::Home->value => [12,2], AgainstSide::Away->value => [17,3] ],
                [ AgainstSide::Home->value => [6,21], AgainstSide::Away->value => [4,13] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [2,7], AgainstSide::Away->value => [19,6] ],
                [ AgainstSide::Home->value => [20,13], AgainstSide::Away->value => [4,12] ],
                [ AgainstSide::Home->value => [16,21], AgainstSide::Away->value => [1,17] ],
                [ AgainstSide::Home->value => [11,5], AgainstSide::Away->value => [9,10] ],
                [ AgainstSide::Home->value => [15,18], AgainstSide::Away->value => [22,3] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [13,18], AgainstSide::Away->value => [20,16] ],
                [ AgainstSide::Home->value => [9,5], AgainstSide::Away->value => [17,22] ],
                [ AgainstSide::Home->value => [11,3], AgainstSide::Away->value => [6,4] ],
                [ AgainstSide::Home->value => [14,15], AgainstSide::Away->value => [12,8] ],
                [ AgainstSide::Home->value => [10,21], AgainstSide::Away->value => [19,1] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [5,4], AgainstSide::Away->value => [1,12] ],
                [ AgainstSide::Home->value => [7,16], AgainstSide::Away->value => [11,21] ],
                [ AgainstSide::Home->value => [15,19], AgainstSide::Away->value => [17,13] ],
                [ AgainstSide::Home->value => [10,22], AgainstSide::Away->value => [9,2] ],
                [ AgainstSide::Home->value => [3,6], AgainstSide::Away->value => [18,8] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [9,17], AgainstSide::Away->value => [6,13] ],
                [ AgainstSide::Home->value => [5,22], AgainstSide::Away->value => [3,10] ],
                [ AgainstSide::Home->value => [4,1], AgainstSide::Away->value => [7,8] ],
                [ AgainstSide::Home->value => [19,14], AgainstSide::Away->value => [11,18] ],
                [ AgainstSide::Home->value => [21,20], AgainstSide::Away->value => [16,12] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [18,1], AgainstSide::Away->value => [10,5] ],
                [ AgainstSide::Home->value => [4,19], AgainstSide::Away->value => [14,9] ],
                [ AgainstSide::Home->value => [22,16], AgainstSide::Away->value => [15,12] ],
                [ AgainstSide::Home->value => [21,3], AgainstSide::Away->value => [20,7] ],
                [ AgainstSide::Home->value => [17,11], AgainstSide::Away->value => [13,2] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [6,17], AgainstSide::Away->value => [22,21] ],
                [ AgainstSide::Home->value => [16,10], AgainstSide::Away->value => [3,20] ],
                [ AgainstSide::Home->value => [19,11], AgainstSide::Away->value => [5,7] ],
                [ AgainstSide::Home->value => [1,8], AgainstSide::Away->value => [18,4] ],
                [ AgainstSide::Home->value => [14,12], AgainstSide::Away->value => [15,9] ],
            ],
            16 => [
                [ AgainstSide::Home->value => [3,4], AgainstSide::Away->value => [10,15] ],
                [ AgainstSide::Home->value => [14,1], AgainstSide::Away->value => [22,6] ],
                [ AgainstSide::Home->value => [2,18], AgainstSide::Away->value => [9,12] ],
                [ AgainstSide::Home->value => [13,16], AgainstSide::Away->value => [7,19] ],
                [ AgainstSide::Home->value => [21,17], AgainstSide::Away->value => [5,8] ],
            ],
            17 => [
                [ AgainstSide::Home->value => [7,14], AgainstSide::Away->value => [16,18] ],
                [ AgainstSide::Home->value => [2,8], AgainstSide::Away->value => [4,10] ],
                [ AgainstSide::Home->value => [13,22], AgainstSide::Away->value => [21,5] ],
                [ AgainstSide::Home->value => [3,1], AgainstSide::Away->value => [6,12] ],
                [ AgainstSide::Home->value => [20,15], AgainstSide::Away->value => [9,11] ],
            ],
            18 => [
                [ AgainstSide::Home->value => [3,2], AgainstSide::Away->value => [8,22] ],
                [ AgainstSide::Home->value => [13,5], AgainstSide::Away->value => [11,15] ],
                [ AgainstSide::Home->value => [20,6], AgainstSide::Away->value => [1,7] ],
                [ AgainstSide::Home->value => [4,9], AgainstSide::Away->value => [19,21] ],
                [ AgainstSide::Home->value => [18,14], AgainstSide::Away->value => [17,10] ],
            ],
            19 => [
                [ AgainstSide::Home->value => [1,6], AgainstSide::Away->value => [21,9] ],
                [ AgainstSide::Home->value => [17,20], AgainstSide::Away->value => [18,19] ],
                [ AgainstSide::Home->value => [14,2], AgainstSide::Away->value => [3,5] ],
                [ AgainstSide::Home->value => [13,12], AgainstSide::Away->value => [8,16] ],
                [ AgainstSide::Home->value => [22,15], AgainstSide::Away->value => [7,4] ],
            ],
            20 => [
                [ AgainstSide::Home->value => [14,10], AgainstSide::Away->value => [13,7] ],
                [ AgainstSide::Home->value => [8,21], AgainstSide::Away->value => [6,9] ],
                [ AgainstSide::Home->value => [11,4], AgainstSide::Away->value => [17,16] ],
                [ AgainstSide::Home->value => [20,19], AgainstSide::Away->value => [22,1] ],
                [ AgainstSide::Home->value => [12,3], AgainstSide::Away->value => [2,5] ],
            ],
            21 => [
                [ AgainstSide::Home->value => [8,11], AgainstSide::Away->value => [12,22] ],
                [ AgainstSide::Home->value => [1,15], AgainstSide::Away->value => [19,3] ],
                [ AgainstSide::Home->value => [2,6], AgainstSide::Away->value => [14,20] ],
                [ AgainstSide::Home->value => [5,18], AgainstSide::Away->value => [21,13] ],
                [ AgainstSide::Home->value => [7,10], AgainstSide::Away->value => [4,17] ],
            ],
            22 => [
                [ AgainstSide::Home->value => [15,2], AgainstSide::Away->value => [21,1] ],
                [ AgainstSide::Home->value => [5,14], AgainstSide::Away->value => [20,4] ],
                [ AgainstSide::Home->value => [13,9], AgainstSide::Away->value => [18,10] ],
                [ AgainstSide::Home->value => [8,17], AgainstSide::Away->value => [7,12] ],
                [ AgainstSide::Home->value => [19,22], AgainstSide::Away->value => [16,6] ],
            ],
            23 => [
                [ AgainstSide::Home->value => [16,3], AgainstSide::Away->value => [17,14] ],
                [ AgainstSide::Home->value => [18,6], AgainstSide::Away->value => [7,11] ],
                [ AgainstSide::Home->value => [12,19], AgainstSide::Away->value => [4,22] ],
                [ AgainstSide::Home->value => [15,21], AgainstSide::Away->value => [2,20] ],
                [ AgainstSide::Home->value => [8,9], AgainstSide::Away->value => [1,5] ],
            ],
        ],
        26 => [
            1 => [
                [ AgainstSide::Home->value => [8,18], AgainstSide::Away->value => [2,4] ],
                [ AgainstSide::Home->value => [10,25], AgainstSide::Away->value => [13,24] ],
                [ AgainstSide::Home->value => [12,16], AgainstSide::Away->value => [22,20] ],
                [ AgainstSide::Home->value => [5,17], AgainstSide::Away->value => [6,26] ],
                [ AgainstSide::Home->value => [14,11], AgainstSide::Away->value => [1,15] ],
                [ AgainstSide::Home->value => [19,7], AgainstSide::Away->value => [21,9] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [16,19], AgainstSide::Away->value => [25,3] ],
                [ AgainstSide::Home->value => [17,7], AgainstSide::Away->value => [14,8] ],
                [ AgainstSide::Home->value => [9,11], AgainstSide::Away->value => [10,4] ],
                [ AgainstSide::Home->value => [20,24], AgainstSide::Away->value => [1,2] ],
                [ AgainstSide::Home->value => [18,22], AgainstSide::Away->value => [13,21] ],
                [ AgainstSide::Home->value => [15,12], AgainstSide::Away->value => [6,23] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [23,20], AgainstSide::Away->value => [18,13] ],
                [ AgainstSide::Home->value => [25,24], AgainstSide::Away->value => [10,19] ],
                [ AgainstSide::Home->value => [2,12], AgainstSide::Away->value => [16,14] ],
                [ AgainstSide::Home->value => [21,3], AgainstSide::Away->value => [17,11] ],
                [ AgainstSide::Home->value => [4,15], AgainstSide::Away->value => [26,8] ],
                [ AgainstSide::Home->value => [5,1], AgainstSide::Away->value => [22,9] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [17,21], AgainstSide::Away->value => [4,22] ],
                [ AgainstSide::Home->value => [2,23], AgainstSide::Away->value => [8,13] ],
                [ AgainstSide::Home->value => [11,19], AgainstSide::Away->value => [5,7] ],
                [ AgainstSide::Home->value => [1,6], AgainstSide::Away->value => [14,15] ],
                [ AgainstSide::Home->value => [3,12], AgainstSide::Away->value => [9,10] ],
                [ AgainstSide::Home->value => [20,26], AgainstSide::Away->value => [16,24] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [10,7], AgainstSide::Away->value => [26,19] ],
                [ AgainstSide::Home->value => [15,20], AgainstSide::Away->value => [3,5] ],
                [ AgainstSide::Home->value => [24,14], AgainstSide::Away->value => [25,22] ],
                [ AgainstSide::Home->value => [17,8], AgainstSide::Away->value => [16,13] ],
                [ AgainstSide::Home->value => [21,4], AgainstSide::Away->value => [1,23] ],
                [ AgainstSide::Home->value => [2,11], AgainstSide::Away->value => [9,18] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [1,12], AgainstSide::Away->value => [5,4] ],
                [ AgainstSide::Home->value => [22,17], AgainstSide::Away->value => [23,10] ],
                [ AgainstSide::Home->value => [11,26], AgainstSide::Away->value => [6,9] ],
                [ AgainstSide::Home->value => [21,14], AgainstSide::Away->value => [2,15] ],
                [ AgainstSide::Home->value => [19,18], AgainstSide::Away->value => [20,3] ],
                [ AgainstSide::Home->value => [25,13], AgainstSide::Away->value => [24,8] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [9,3], AgainstSide::Away->value => [7,25] ],
                [ AgainstSide::Home->value => [8,4], AgainstSide::Away->value => [26,14] ],
                [ AgainstSide::Home->value => [23,24], AgainstSide::Away->value => [20,17] ],
                [ AgainstSide::Home->value => [5,11], AgainstSide::Away->value => [13,19] ],
                [ AgainstSide::Home->value => [16,2], AgainstSide::Away->value => [10,6] ],
                [ AgainstSide::Home->value => [22,1], AgainstSide::Away->value => [18,15] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [15,8], AgainstSide::Away->value => [24,12] ],
                [ AgainstSide::Home->value => [13,17], AgainstSide::Away->value => [7,9] ],
                [ AgainstSide::Home->value => [16,22], AgainstSide::Away->value => [14,19] ],
                [ AgainstSide::Home->value => [3,10], AgainstSide::Away->value => [1,20] ],
                [ AgainstSide::Home->value => [21,26], AgainstSide::Away->value => [25,18] ],
                [ AgainstSide::Home->value => [6,5], AgainstSide::Away->value => [23,11] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [17,16], AgainstSide::Away->value => [18,11] ],
                [ AgainstSide::Home->value => [25,26], AgainstSide::Away->value => [3,23] ],
                [ AgainstSide::Home->value => [8,20], AgainstSide::Away->value => [13,4] ],
                [ AgainstSide::Home->value => [19,9], AgainstSide::Away->value => [12,6] ],
                [ AgainstSide::Home->value => [10,1], AgainstSide::Away->value => [5,2] ],
                [ AgainstSide::Home->value => [21,22], AgainstSide::Away->value => [7,14] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [12,10], AgainstSide::Away->value => [8,9] ],
                [ AgainstSide::Home->value => [19,22], AgainstSide::Away->value => [1,16] ],
                [ AgainstSide::Home->value => [5,15], AgainstSide::Away->value => [24,2] ],
                [ AgainstSide::Home->value => [25,20], AgainstSide::Away->value => [7,26] ],
                [ AgainstSide::Home->value => [13,11], AgainstSide::Away->value => [23,14] ],
                [ AgainstSide::Home->value => [18,6], AgainstSide::Away->value => [17,4] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [1,25], AgainstSide::Away->value => [20,6] ],
                [ AgainstSide::Home->value => [4,9], AgainstSide::Away->value => [11,3] ],
                [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [12,19] ],
                [ AgainstSide::Home->value => [16,5], AgainstSide::Away->value => [21,8] ],
                [ AgainstSide::Home->value => [2,22], AgainstSide::Away->value => [24,26] ],
                [ AgainstSide::Home->value => [10,14], AgainstSide::Away->value => [7,23] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [26,13], AgainstSide::Away->value => [11,10] ],
                [ AgainstSide::Home->value => [16,21], AgainstSide::Away->value => [6,24] ],
                [ AgainstSide::Home->value => [1,9], AgainstSide::Away->value => [8,23] ],
                [ AgainstSide::Home->value => [7,12], AgainstSide::Away->value => [15,3] ],
                [ AgainstSide::Home->value => [5,25], AgainstSide::Away->value => [14,4] ],
                [ AgainstSide::Home->value => [20,2], AgainstSide::Away->value => [19,17] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [15,9], AgainstSide::Away->value => [17,14] ],
                [ AgainstSide::Home->value => [19,4], AgainstSide::Away->value => [18,12] ],
                [ AgainstSide::Home->value => [13,6], AgainstSide::Away->value => [21,25] ],
                [ AgainstSide::Home->value => [8,2], AgainstSide::Away->value => [22,23] ],
                [ AgainstSide::Home->value => [11,16], AgainstSide::Away->value => [7,20] ],
                [ AgainstSide::Home->value => [1,24], AgainstSide::Away->value => [3,26] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [23,5], AgainstSide::Away->value => [22,7] ],
                [ AgainstSide::Home->value => [26,15], AgainstSide::Away->value => [20,11] ],
                [ AgainstSide::Home->value => [3,8], AgainstSide::Away->value => [18,16] ],
                [ AgainstSide::Home->value => [4,1], AgainstSide::Away->value => [10,13] ],
                [ AgainstSide::Home->value => [9,24], AgainstSide::Away->value => [6,21] ],
                [ AgainstSide::Home->value => [12,25], AgainstSide::Away->value => [17,2] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [11,1], AgainstSide::Away->value => [12,21] ],
                [ AgainstSide::Home->value => [3,16], AgainstSide::Away->value => [5,22] ],
                [ AgainstSide::Home->value => [23,17], AgainstSide::Away->value => [25,15] ],
                [ AgainstSide::Home->value => [24,18], AgainstSide::Away->value => [9,14] ],
                [ AgainstSide::Home->value => [13,7], AgainstSide::Away->value => [2,26] ],
                [ AgainstSide::Home->value => [6,19], AgainstSide::Away->value => [8,10] ],
            ],
            16 => [
                [ AgainstSide::Home->value => [25,23], AgainstSide::Away->value => [16,6] ],
                [ AgainstSide::Home->value => [2,10], AgainstSide::Away->value => [21,18] ],
                [ AgainstSide::Home->value => [3,7], AgainstSide::Away->value => [4,24] ],
                [ AgainstSide::Home->value => [15,19], AgainstSide::Away->value => [11,22] ],
                [ AgainstSide::Home->value => [17,9], AgainstSide::Away->value => [5,20] ],
                [ AgainstSide::Home->value => [26,12], AgainstSide::Away->value => [14,13] ],
            ],
            17 => [
                [ AgainstSide::Home->value => [3,4], AgainstSide::Away->value => [19,2] ],
                [ AgainstSide::Home->value => [14,1], AgainstSide::Away->value => [16,7] ],
                [ AgainstSide::Home->value => [15,23], AgainstSide::Away->value => [10,21] ],
                [ AgainstSide::Home->value => [9,13], AgainstSide::Away->value => [26,18] ],
                [ AgainstSide::Home->value => [25,6], AgainstSide::Away->value => [12,22] ],
                [ AgainstSide::Home->value => [11,8], AgainstSide::Away->value => [5,24] ],
            ],
            18 => [
                [ AgainstSide::Home->value => [10,26], AgainstSide::Away->value => [8,22] ],
                [ AgainstSide::Home->value => [15,21], AgainstSide::Away->value => [9,20] ],
                [ AgainstSide::Home->value => [5,14], AgainstSide::Away->value => [3,18] ],
                [ AgainstSide::Home->value => [2,7], AgainstSide::Away->value => [11,25] ],
                [ AgainstSide::Home->value => [1,19], AgainstSide::Away->value => [17,24] ],
                [ AgainstSide::Home->value => [16,23], AgainstSide::Away->value => [4,12] ],
            ],
            19 => [
                [ AgainstSide::Home->value => [20,16], AgainstSide::Away->value => [9,23] ],
                [ AgainstSide::Home->value => [18,2], AgainstSide::Away->value => [7,1] ],
                [ AgainstSide::Home->value => [4,26], AgainstSide::Away->value => [19,21] ],
                [ AgainstSide::Home->value => [10,24], AgainstSide::Away->value => [12,14] ],
                [ AgainstSide::Home->value => [22,13], AgainstSide::Away->value => [6,3] ],
                [ AgainstSide::Home->value => [8,5], AgainstSide::Away->value => [15,17] ],
            ],
            20 => [
                [ AgainstSide::Home->value => [3,24], AgainstSide::Away->value => [13,5] ],
                [ AgainstSide::Home->value => [22,26], AgainstSide::Away->value => [12,17] ],
                [ AgainstSide::Home->value => [7,6], AgainstSide::Away->value => [18,1] ],
                [ AgainstSide::Home->value => [4,23], AgainstSide::Away->value => [25,9] ],
                [ AgainstSide::Home->value => [19,8], AgainstSide::Away->value => [15,10] ],
                [ AgainstSide::Home->value => [14,20], AgainstSide::Away->value => [11,21] ],
            ],
            21 => [
                [ AgainstSide::Home->value => [6,14], AgainstSide::Away->value => [19,20] ],
                [ AgainstSide::Home->value => [23,18], AgainstSide::Away->value => [24,11] ],
                [ AgainstSide::Home->value => [22,10], AgainstSide::Away->value => [2,25] ],
                [ AgainstSide::Home->value => [7,21], AgainstSide::Away->value => [5,12] ],
                [ AgainstSide::Home->value => [26,17], AgainstSide::Away->value => [3,1] ],
                [ AgainstSide::Home->value => [13,15], AgainstSide::Away->value => [16,4] ],
            ],
            22 => [
                [ AgainstSide::Home->value => [24,15], AgainstSide::Away->value => [7,18] ],
                [ AgainstSide::Home->value => [6,2], AgainstSide::Away->value => [5,26] ],
                [ AgainstSide::Home->value => [9,16], AgainstSide::Away->value => [1,13] ],
                [ AgainstSide::Home->value => [14,22], AgainstSide::Away->value => [4,20] ],
                [ AgainstSide::Home->value => [8,25], AgainstSide::Away->value => [12,11] ],
                [ AgainstSide::Home->value => [19,3], AgainstSide::Away->value => [23,21] ],
            ],
            23 => [
                [ AgainstSide::Home->value => [14,3], AgainstSide::Away->value => [6,8] ],
                [ AgainstSide::Home->value => [20,13], AgainstSide::Away->value => [22,15] ],
                [ AgainstSide::Home->value => [26,23], AgainstSide::Away->value => [21,2] ],
                [ AgainstSide::Home->value => [12,9], AgainstSide::Away->value => [19,24] ],
                [ AgainstSide::Home->value => [7,4], AgainstSide::Away->value => [10,17] ],
                [ AgainstSide::Home->value => [5,18], AgainstSide::Away->value => [25,16] ],
            ],
            24 => [
                [ AgainstSide::Home->value => [25,17], AgainstSide::Away->value => [21,1] ],
                [ AgainstSide::Home->value => [4,6], AgainstSide::Away->value => [24,7] ],
                [ AgainstSide::Home->value => [20,18], AgainstSide::Away->value => [8,12] ],
                [ AgainstSide::Home->value => [10,16], AgainstSide::Away->value => [15,11] ],
                [ AgainstSide::Home->value => [23,13], AgainstSide::Away->value => [19,5] ],
                [ AgainstSide::Home->value => [9,2], AgainstSide::Away->value => [3,22] ],
            ],
            25 => [
                [ AgainstSide::Home->value => [24,22], AgainstSide::Away->value => [4,11] ],
                [ AgainstSide::Home->value => [12,13], AgainstSide::Away->value => [17,1] ],
                [ AgainstSide::Home->value => [14,25], AgainstSide::Away->value => [9,5] ],
                [ AgainstSide::Home->value => [8,7], AgainstSide::Away->value => [20,21] ],
                [ AgainstSide::Home->value => [2,3], AgainstSide::Away->value => [15,6] ],
                [ AgainstSide::Home->value => [18,10], AgainstSide::Away->value => [26,16] ],
            ],
            26 => [
                [ AgainstSide::Home->value => [13,2], AgainstSide::Away->value => [12,20] ],
                [ AgainstSide::Home->value => [18,14], AgainstSide::Away->value => [10,5] ],
                [ AgainstSide::Home->value => [6,11], AgainstSide::Away->value => [17,3] ],
                [ AgainstSide::Home->value => [23,19], AgainstSide::Away->value => [26,1] ],
                [ AgainstSide::Home->value => [24,21], AgainstSide::Away->value => [8,16] ],
                [ AgainstSide::Home->value => [4,25], AgainstSide::Away->value => [15,7] ],
            ],
            27 => [
                [ AgainstSide::Home->value => [9,26], AgainstSide::Away->value => [15,16] ],
                [ AgainstSide::Home->value => [1,8], AgainstSide::Away->value => [19,25] ],
                [ AgainstSide::Home->value => [21,5], AgainstSide::Away->value => [20,10] ],
                [ AgainstSide::Home->value => [18,4], AgainstSide::Away->value => [22,6] ],
                [ AgainstSide::Home->value => [12,23], AgainstSide::Away->value => [11,7] ],
                [ AgainstSide::Home->value => [2,14], AgainstSide::Away->value => [13,3] ],
            ],
        ],
    ];

    /**
     * @param list<int> $placeNrs
     * @return array<int, list<TwoVsTwoHomeAway>>
     */
    public static function create(array $placeNrs): array {
        $nrOfPlaces = count($placeNrs);
        if( !array_key_exists($nrOfPlaces, self::$map) ) {
            throw new \Exception('implement solution');
        }
        return array_map(function(array $cyclePartHomeAways ) use($placeNrs): array {
            return array_map(function(array $homeAwayAsNumbers ) use($placeNrs): TwoVsTwoHomeAway{
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
            }, $cyclePartHomeAways );
        }, self::$map[$nrOfPlaces] );
    }
}
