<?php

namespace SportsScheduler\Schedules\CycleCreators\Helpers;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class Solutions4NPlus3
{
    /**
     * @var array<int, array<int, list<array<string, list<int>>>>> $map
     */
    private static array $map = [
        7 => [
            1 => [
                [ AgainstSide::Home->value => [7,5], AgainstSide::Away->value => [4,6] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [6,2], AgainstSide::Away->value => [1,5] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [5,3], AgainstSide::Away->value => [7,2] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [2,4], AgainstSide::Away->value => [6,3] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [3,1], AgainstSide::Away->value => [5,4] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [4,7], AgainstSide::Away->value => [2,1] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [1,6], AgainstSide::Away->value => [3,7] ],
            ],
        ],
        11 => [
            1 => [
                [ AgainstSide::Home->value => [4,2], AgainstSide::Away->value => [1,10] ],
                [ AgainstSide::Home->value => [7,6], AgainstSide::Away->value => [5,8] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [11,8], AgainstSide::Away->value => [3,9] ],
                [ AgainstSide::Home->value => [2,5], AgainstSide::Away->value => [6,4] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [5,3], AgainstSide::Away->value => [8,2] ],
                [ AgainstSide::Home->value => [10,9], AgainstSide::Away->value => [1,7] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [4,7], AgainstSide::Away->value => [6,11] ],
                [ AgainstSide::Home->value => [3,1], AgainstSide::Away->value => [9,5] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [1,6], AgainstSide::Away->value => [7,3] ],
                [ AgainstSide::Home->value => [2,11], AgainstSide::Away->value => [8,10] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [5,10], AgainstSide::Away->value => [9,4] ],
                [ AgainstSide::Home->value => [6,8], AgainstSide::Away->value => [11,1] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [8,9], AgainstSide::Away->value => [10,6] ],
                [ AgainstSide::Home->value => [3,4], AgainstSide::Away->value => [7,2] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [11,5] ],
                [ AgainstSide::Home->value => [9,7], AgainstSide::Away->value => [4,8] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [7,11], AgainstSide::Away->value => [2,9] ],
                [ AgainstSide::Home->value => [6,5], AgainstSide::Away->value => [10,3] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [8,3], AgainstSide::Away->value => [4,1] ],
                [ AgainstSide::Home->value => [11,10], AgainstSide::Away->value => [5,7] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [10,4], AgainstSide::Away->value => [3,11] ],
                [ AgainstSide::Home->value => [9,1], AgainstSide::Away->value => [2,6] ],
            ],
        ],
        15 => [
            1 => [
                [ AgainstSide::Home->value => [2,8], AgainstSide::Away->value => [3,4] ],
                [ AgainstSide::Home->value => [5,15], AgainstSide::Away->value => [12,9] ],
                [ AgainstSide::Home->value => [6,10], AgainstSide::Away->value => [11,13] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [3,9], AgainstSide::Away->value => [4,5] ],
                [ AgainstSide::Home->value => [7,11], AgainstSide::Away->value => [12,14] ],
                [ AgainstSide::Home->value => [6,1], AgainstSide::Away->value => [13,10] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [8,12], AgainstSide::Away->value => [13,15] ],
                [ AgainstSide::Home->value => [7,2], AgainstSide::Away->value => [14,11] ],
                [ AgainstSide::Home->value => [4,10], AgainstSide::Away->value => [5,6] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [5,11], AgainstSide::Away->value => [6,7] ],
                [ AgainstSide::Home->value => [8,3], AgainstSide::Away->value => [15,12] ],
                [ AgainstSide::Home->value => [9,13], AgainstSide::Away->value => [14,1] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [6,12], AgainstSide::Away->value => [7,8] ],
                [ AgainstSide::Home->value => [10,14], AgainstSide::Away->value => [15,2] ],
                [ AgainstSide::Home->value => [9,4], AgainstSide::Away->value => [1,13] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [11,15], AgainstSide::Away->value => [1,3] ],
                [ AgainstSide::Home->value => [10,5], AgainstSide::Away->value => [2,14] ],
                [ AgainstSide::Home->value => [7,13], AgainstSide::Away->value => [8,9] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [8,14], AgainstSide::Away->value => [9,10] ],
                [ AgainstSide::Home->value => [11,6], AgainstSide::Away->value => [3,15] ],
                [ AgainstSide::Home->value => [12,1], AgainstSide::Away->value => [2,4] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [9,15], AgainstSide::Away->value => [10,11] ],
                [ AgainstSide::Home->value => [13,2], AgainstSide::Away->value => [3,5] ],
                [ AgainstSide::Home->value => [12,7], AgainstSide::Away->value => [4,1] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [14,3], AgainstSide::Away->value => [4,6] ],
                [ AgainstSide::Home->value => [13,8], AgainstSide::Away->value => [5,2] ],
                [ AgainstSide::Home->value => [10,1], AgainstSide::Away->value => [11,12] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [11,2], AgainstSide::Away->value => [12,13] ],
                [ AgainstSide::Home->value => [14,9], AgainstSide::Away->value => [6,3] ],
                [ AgainstSide::Home->value => [15,4], AgainstSide::Away->value => [5,7] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [12,3], AgainstSide::Away->value => [13,14] ],
                [ AgainstSide::Home->value => [1,5], AgainstSide::Away->value => [6,8] ],
                [ AgainstSide::Home->value => [15,10], AgainstSide::Away->value => [7,4] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [2,6], AgainstSide::Away->value => [7,9] ],
                [ AgainstSide::Home->value => [1,11], AgainstSide::Away->value => [8,5] ],
                [ AgainstSide::Home->value => [13,4], AgainstSide::Away->value => [14,15] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [14,5], AgainstSide::Away->value => [15,1] ],
                [ AgainstSide::Home->value => [2,12], AgainstSide::Away->value => [9,6] ],
                [ AgainstSide::Home->value => [3,7], AgainstSide::Away->value => [8,10] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [15,6], AgainstSide::Away->value => [1,2] ],
                [ AgainstSide::Home->value => [4,8], AgainstSide::Away->value => [9,11] ],
                [ AgainstSide::Home->value => [3,13], AgainstSide::Away->value => [10,7] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [5,9], AgainstSide::Away->value => [10,12] ],
                [ AgainstSide::Home->value => [4,14], AgainstSide::Away->value => [11,8] ],
                [ AgainstSide::Home->value => [1,7], AgainstSide::Away->value => [2,3] ],
            ],
        ],
        19 => [
            1 => [
                [ AgainstSide::Home->value => [2,3], AgainstSide::Away->value => [5,11] ],
                [ AgainstSide::Home->value => [4,6], AgainstSide::Away->value => [8,13] ],
                [ AgainstSide::Home->value => [9,17], AgainstSide::Away->value => [12,16] ],
                [ AgainstSide::Home->value => [10,19], AgainstSide::Away->value => [15,18] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [8,9], AgainstSide::Away->value => [11,17] ],
                [ AgainstSide::Home->value => [10,12], AgainstSide::Away->value => [14,19] ],
                [ AgainstSide::Home->value => [15,4], AgainstSide::Away->value => [18,3] ],
                [ AgainstSide::Home->value => [16,6], AgainstSide::Away->value => [2,5] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [14,15], AgainstSide::Away->value => [17,4] ],
                [ AgainstSide::Home->value => [16,18], AgainstSide::Away->value => [1,6] ],
                [ AgainstSide::Home->value => [2,10], AgainstSide::Away->value => [5,9] ],
                [ AgainstSide::Home->value => [3,12], AgainstSide::Away->value => [8,11] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [4,10] ],
                [ AgainstSide::Home->value => [3,5], AgainstSide::Away->value => [7,12] ],
                [ AgainstSide::Home->value => [8,16], AgainstSide::Away->value => [11,15] ],
                [ AgainstSide::Home->value => [9,18], AgainstSide::Away->value => [14,17] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [7,8], AgainstSide::Away->value => [10,16] ],
                [ AgainstSide::Home->value => [9,11], AgainstSide::Away->value => [13,18] ],
                [ AgainstSide::Home->value => [14,3], AgainstSide::Away->value => [17,2] ],
                [ AgainstSide::Home->value => [15,5], AgainstSide::Away->value => [1,4] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [13,14], AgainstSide::Away->value => [16,3] ],
                [ AgainstSide::Home->value => [15,17], AgainstSide::Away->value => [19,5] ],
                [ AgainstSide::Home->value => [1,9], AgainstSide::Away->value => [4,8] ],
                [ AgainstSide::Home->value => [2,11], AgainstSide::Away->value => [7,10] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [19,1], AgainstSide::Away->value => [3,9] ],
                [ AgainstSide::Home->value => [2,4], AgainstSide::Away->value => [6,11] ],
                [ AgainstSide::Home->value => [7,15], AgainstSide::Away->value => [10,14] ],
                [ AgainstSide::Home->value => [8,17], AgainstSide::Away->value => [13,16] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [9,15] ],
                [ AgainstSide::Home->value => [8,10], AgainstSide::Away->value => [12,17] ],
                [ AgainstSide::Home->value => [13,2], AgainstSide::Away->value => [16,1] ],
                [ AgainstSide::Home->value => [14,4], AgainstSide::Away->value => [19,3] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [12,13], AgainstSide::Away->value => [15,2] ],
                [ AgainstSide::Home->value => [14,16], AgainstSide::Away->value => [18,4] ],
                [ AgainstSide::Home->value => [19,8], AgainstSide::Away->value => [3,7] ],
                [ AgainstSide::Home->value => [1,10], AgainstSide::Away->value => [6,9] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [18,19], AgainstSide::Away->value => [2,8] ],
                [ AgainstSide::Home->value => [1,3], AgainstSide::Away->value => [5,10] ],
                [ AgainstSide::Home->value => [6,14], AgainstSide::Away->value => [9,13] ],
                [ AgainstSide::Home->value => [7,16], AgainstSide::Away->value => [12,15] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [5,6], AgainstSide::Away->value => [8,14] ],
                [ AgainstSide::Home->value => [7,9], AgainstSide::Away->value => [11,16] ],
                [ AgainstSide::Home->value => [12,1], AgainstSide::Away->value => [15,19] ],
                [ AgainstSide::Home->value => [13,3], AgainstSide::Away->value => [18,2] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [11,12], AgainstSide::Away->value => [14,1] ],
                [ AgainstSide::Home->value => [13,15], AgainstSide::Away->value => [17,3] ],
                [ AgainstSide::Home->value => [18,7], AgainstSide::Away->value => [2,6] ],
                [ AgainstSide::Home->value => [19,9], AgainstSide::Away->value => [5,8] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [1,7] ],
                [ AgainstSide::Home->value => [19,2], AgainstSide::Away->value => [4,9] ],
                [ AgainstSide::Home->value => [5,13], AgainstSide::Away->value => [8,12] ],
                [ AgainstSide::Home->value => [6,15], AgainstSide::Away->value => [11,14] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [4,5], AgainstSide::Away->value => [7,13] ],
                [ AgainstSide::Home->value => [6,8], AgainstSide::Away->value => [10,15] ],
                [ AgainstSide::Home->value => [11,19], AgainstSide::Away->value => [14,18] ],
                [ AgainstSide::Home->value => [12,2], AgainstSide::Away->value => [17,1] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [10,11], AgainstSide::Away->value => [13,19] ],
                [ AgainstSide::Home->value => [12,14], AgainstSide::Away->value => [16,2] ],
                [ AgainstSide::Home->value => [17,6], AgainstSide::Away->value => [1,5] ],
                [ AgainstSide::Home->value => [18,8], AgainstSide::Away->value => [4,7] ],
            ],
            16 => [
                [ AgainstSide::Home->value => [16,17], AgainstSide::Away->value => [19,6] ],
                [ AgainstSide::Home->value => [18,1], AgainstSide::Away->value => [3,8] ],
                [ AgainstSide::Home->value => [4,12], AgainstSide::Away->value => [7,11] ],
                [ AgainstSide::Home->value => [5,14], AgainstSide::Away->value => [10,13] ],
            ],
            17 => [
                [ AgainstSide::Home->value => [3,4], AgainstSide::Away->value => [6,12] ],
                [ AgainstSide::Home->value => [5,7], AgainstSide::Away->value => [9,14] ],
                [ AgainstSide::Home->value => [10,18], AgainstSide::Away->value => [13,17] ],
                [ AgainstSide::Home->value => [11,1], AgainstSide::Away->value => [16,19] ],
            ],
            18 => [
                [ AgainstSide::Home->value => [9,10], AgainstSide::Away->value => [12,18] ],
                [ AgainstSide::Home->value => [11,13], AgainstSide::Away->value => [15,1] ],
                [ AgainstSide::Home->value => [16,5], AgainstSide::Away->value => [19,4] ],
                [ AgainstSide::Home->value => [17,7], AgainstSide::Away->value => [3,6] ],
            ],
            19 => [
                [ AgainstSide::Home->value => [15,16], AgainstSide::Away->value => [18,5] ],
                [ AgainstSide::Home->value => [17,19], AgainstSide::Away->value => [2,7] ],
                [ AgainstSide::Home->value => [3,11], AgainstSide::Away->value => [6,10] ],
                [ AgainstSide::Home->value => [4,13], AgainstSide::Away->value => [9,12] ],
            ],
        ],
        23 => [
            1 => [
                [ AgainstSide::Home->value => [2,3], AgainstSide::Away->value => [20,11] ],
                [ AgainstSide::Home->value => [4,7], AgainstSide::Away->value => [8,16] ],
                [ AgainstSide::Home->value => [5,10], AgainstSide::Away->value => [12,23] ],
                [ AgainstSide::Home->value => [6,13], AgainstSide::Away->value => [17,19] ],
                [ AgainstSide::Home->value => [14,18], AgainstSide::Away->value => [15,21] ],
            ],
            2 => [
                [ AgainstSide::Home->value => [5,6], AgainstSide::Away->value => [23,14] ],
                [ AgainstSide::Home->value => [17,21], AgainstSide::Away->value => [18,1] ],
                [ AgainstSide::Home->value => [8,13], AgainstSide::Away->value => [15,3] ],
                [ AgainstSide::Home->value => [9,16], AgainstSide::Away->value => [20,22] ],
                [ AgainstSide::Home->value => [7,10], AgainstSide::Away->value => [11,19] ],
            ],
            3 => [
                [ AgainstSide::Home->value => [8,9], AgainstSide::Away->value => [3,17] ],
                [ AgainstSide::Home->value => [10,13], AgainstSide::Away->value => [14,22] ],
                [ AgainstSide::Home->value => [11,16], AgainstSide::Away->value => [18,6] ],
                [ AgainstSide::Home->value => [12,19], AgainstSide::Away->value => [23,2] ],
                [ AgainstSide::Home->value => [20,1], AgainstSide::Away->value => [21,4] ],
            ],
            4 => [
                [ AgainstSide::Home->value => [11,12], AgainstSide::Away->value => [6,20] ],
                [ AgainstSide::Home->value => [23,4], AgainstSide::Away->value => [1,7] ],
                [ AgainstSide::Home->value => [14,19], AgainstSide::Away->value => [21,9] ],
                [ AgainstSide::Home->value => [15,22], AgainstSide::Away->value => [3,5] ],
                [ AgainstSide::Home->value => [13,16], AgainstSide::Away->value => [17,2] ],
            ],
            5 => [
                [ AgainstSide::Home->value => [14,15], AgainstSide::Away->value => [9,23] ],
                [ AgainstSide::Home->value => [16,19], AgainstSide::Away->value => [20,5] ],
                [ AgainstSide::Home->value => [17,22], AgainstSide::Away->value => [1,12] ],
                [ AgainstSide::Home->value => [18,2], AgainstSide::Away->value => [6,8] ],
                [ AgainstSide::Home->value => [3,7], AgainstSide::Away->value => [4,10] ],
            ],
            6 => [
                [ AgainstSide::Home->value => [17,18], AgainstSide::Away->value => [12,3] ],
                [ AgainstSide::Home->value => [6,10], AgainstSide::Away->value => [7,13] ],
                [ AgainstSide::Home->value => [20,2], AgainstSide::Away->value => [4,15] ],
                [ AgainstSide::Home->value => [21,5], AgainstSide::Away->value => [9,11] ],
                [ AgainstSide::Home->value => [19,22], AgainstSide::Away->value => [23,8] ],
            ],
            7 => [
                [ AgainstSide::Home->value => [20,21], AgainstSide::Away->value => [15,6] ],
                [ AgainstSide::Home->value => [22,2], AgainstSide::Away->value => [3,11] ],
                [ AgainstSide::Home->value => [23,5], AgainstSide::Away->value => [7,18] ],
                [ AgainstSide::Home->value => [1,8], AgainstSide::Away->value => [12,14] ],
                [ AgainstSide::Home->value => [9,13], AgainstSide::Away->value => [10,16] ],
            ],
            8 => [
                [ AgainstSide::Home->value => [23,1], AgainstSide::Away->value => [18,9] ],
                [ AgainstSide::Home->value => [12,16], AgainstSide::Away->value => [13,19] ],
                [ AgainstSide::Home->value => [3,8], AgainstSide::Away->value => [10,21] ],
                [ AgainstSide::Home->value => [4,11], AgainstSide::Away->value => [15,17] ],
                [ AgainstSide::Home->value => [2,5], AgainstSide::Away->value => [6,14] ],
            ],
            9 => [
                [ AgainstSide::Home->value => [3,4], AgainstSide::Away->value => [21,12] ],
                [ AgainstSide::Home->value => [5,8], AgainstSide::Away->value => [9,17] ],
                [ AgainstSide::Home->value => [6,11], AgainstSide::Away->value => [13,1] ],
                [ AgainstSide::Home->value => [7,14], AgainstSide::Away->value => [18,20] ],
                [ AgainstSide::Home->value => [15,19], AgainstSide::Away->value => [16,22] ],
            ],
            10 => [
                [ AgainstSide::Home->value => [6,7], AgainstSide::Away->value => [1,15] ],
                [ AgainstSide::Home->value => [18,22], AgainstSide::Away->value => [19,2] ],
                [ AgainstSide::Home->value => [9,14], AgainstSide::Away->value => [16,4] ],
                [ AgainstSide::Home->value => [10,17], AgainstSide::Away->value => [21,23] ],
                [ AgainstSide::Home->value => [8,11], AgainstSide::Away->value => [12,20] ],
            ],
            11 => [
                [ AgainstSide::Home->value => [9,10], AgainstSide::Away->value => [4,18] ],
                [ AgainstSide::Home->value => [11,14], AgainstSide::Away->value => [15,23] ],
                [ AgainstSide::Home->value => [12,17], AgainstSide::Away->value => [19,7] ],
                [ AgainstSide::Home->value => [13,20], AgainstSide::Away->value => [1,3] ],
                [ AgainstSide::Home->value => [21,2], AgainstSide::Away->value => [22,5] ],
            ],
            12 => [
                [ AgainstSide::Home->value => [12,13], AgainstSide::Away->value => [7,21] ],
                [ AgainstSide::Home->value => [1,5], AgainstSide::Away->value => [2,8] ],
                [ AgainstSide::Home->value => [15,20], AgainstSide::Away->value => [22,10] ],
                [ AgainstSide::Home->value => [16,23], AgainstSide::Away->value => [4,6] ],
                [ AgainstSide::Home->value => [14,17], AgainstSide::Away->value => [18,3] ],
            ],
            13 => [
                [ AgainstSide::Home->value => [15,16], AgainstSide::Away->value => [10,1] ],
                [ AgainstSide::Home->value => [17,20], AgainstSide::Away->value => [21,6] ],
                [ AgainstSide::Home->value => [18,23], AgainstSide::Away->value => [2,13] ],
                [ AgainstSide::Home->value => [19,3], AgainstSide::Away->value => [7,9] ],
                [ AgainstSide::Home->value => [4,8], AgainstSide::Away->value => [5,11] ],
            ],
            14 => [
                [ AgainstSide::Home->value => [18,19], AgainstSide::Away->value => [13,4] ],
                [ AgainstSide::Home->value => [7,11], AgainstSide::Away->value => [8,14] ],
                [ AgainstSide::Home->value => [21,3], AgainstSide::Away->value => [5,16] ],
                [ AgainstSide::Home->value => [22,6], AgainstSide::Away->value => [10,12] ],
                [ AgainstSide::Home->value => [20,23], AgainstSide::Away->value => [1,9] ],
            ],
            15 => [
                [ AgainstSide::Home->value => [21,22], AgainstSide::Away->value => [16,7] ],
                [ AgainstSide::Home->value => [23,3], AgainstSide::Away->value => [4,12] ],
                [ AgainstSide::Home->value => [1,6], AgainstSide::Away->value => [8,19] ],
                [ AgainstSide::Home->value => [2,9], AgainstSide::Away->value => [13,15] ],
                [ AgainstSide::Home->value => [10,14], AgainstSide::Away->value => [11,17] ],
            ],
            16 => [
                [ AgainstSide::Home->value => [1,2], AgainstSide::Away->value => [19,10] ],
                [ AgainstSide::Home->value => [13,17], AgainstSide::Away->value => [14,20] ],
                [ AgainstSide::Home->value => [4,9], AgainstSide::Away->value => [11,22] ],
                [ AgainstSide::Home->value => [5,12], AgainstSide::Away->value => [16,18] ],
                [ AgainstSide::Home->value => [3,6], AgainstSide::Away->value => [7,15] ],
            ],
            17 => [
                [ AgainstSide::Home->value => [4,5], AgainstSide::Away->value => [22,13] ],
                [ AgainstSide::Home->value => [6,9], AgainstSide::Away->value => [10,18] ],
                [ AgainstSide::Home->value => [7,12], AgainstSide::Away->value => [14,2] ],
                [ AgainstSide::Home->value => [8,15], AgainstSide::Away->value => [19,21] ],
                [ AgainstSide::Home->value => [16,20], AgainstSide::Away->value => [17,23] ],
            ],
            18 => [
                [ AgainstSide::Home->value => [7,8], AgainstSide::Away->value => [2,16] ],
                [ AgainstSide::Home->value => [19,23], AgainstSide::Away->value => [20,3] ],
                [ AgainstSide::Home->value => [10,15], AgainstSide::Away->value => [17,5] ],
                [ AgainstSide::Home->value => [11,18], AgainstSide::Away->value => [22,1] ],
                [ AgainstSide::Home->value => [9,12], AgainstSide::Away->value => [13,21] ],
            ],
            19 => [
                [ AgainstSide::Home->value => [10,11], AgainstSide::Away->value => [5,19] ],
                [ AgainstSide::Home->value => [12,15], AgainstSide::Away->value => [16,1] ],
                [ AgainstSide::Home->value => [13,18], AgainstSide::Away->value => [20,8] ],
                [ AgainstSide::Home->value => [14,21], AgainstSide::Away->value => [2,4] ],
                [ AgainstSide::Home->value => [22,3], AgainstSide::Away->value => [23,6] ],
            ],
            20 => [
                [ AgainstSide::Home->value => [13,14], AgainstSide::Away->value => [8,22] ],
                [ AgainstSide::Home->value => [2,6], AgainstSide::Away->value => [3,9] ],
                [ AgainstSide::Home->value => [16,21], AgainstSide::Away->value => [23,11] ],
                [ AgainstSide::Home->value => [17,1], AgainstSide::Away->value => [5,7] ],
                [ AgainstSide::Home->value => [15,18], AgainstSide::Away->value => [19,4] ],
            ],
            21 => [
                [ AgainstSide::Home->value => [16,17], AgainstSide::Away->value => [11,2] ],
                [ AgainstSide::Home->value => [18,21], AgainstSide::Away->value => [22,7] ],
                [ AgainstSide::Home->value => [19,1], AgainstSide::Away->value => [3,14] ],
                [ AgainstSide::Home->value => [20,4], AgainstSide::Away->value => [8,10] ],
                [ AgainstSide::Home->value => [5,9], AgainstSide::Away->value => [6,12] ],
            ],
            22 => [
                [ AgainstSide::Home->value => [19,20], AgainstSide::Away->value => [14,5] ],
                [ AgainstSide::Home->value => [8,12], AgainstSide::Away->value => [9,15] ],
                [ AgainstSide::Home->value => [22,4], AgainstSide::Away->value => [6,17] ],
                [ AgainstSide::Home->value => [23,7], AgainstSide::Away->value => [11,13] ],
                [ AgainstSide::Home->value => [21,1], AgainstSide::Away->value => [2,10] ],
            ],
            23 => [
                [ AgainstSide::Home->value => [22,23], AgainstSide::Away->value => [17,8] ],
                [ AgainstSide::Home->value => [1,4], AgainstSide::Away->value => [5,13] ],
                [ AgainstSide::Home->value => [2,7], AgainstSide::Away->value => [9,20] ],
                [ AgainstSide::Home->value => [3,10], AgainstSide::Away->value => [14,16] ],
                [ AgainstSide::Home->value => [11,15], AgainstSide::Away->value => [12,18] ],
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
