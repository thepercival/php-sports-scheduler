<?php

$file = "/home/coen/projecten/php-sports-scheduler/domain/Schedules/CycleCreators/Helpers/coen.txt";
$content = file_get_contents($file);
$newContent = "";
if ($content !== false) {
    $lines = explode(PHP_EOL, $content); // Split the content into lines
    foreach ($lines as $line) {
        if( strlen($line) === 0) {
            continue;
        }
        // echo $line . PHP_EOL;
        $nrOfPlacesStartPos = strpos($line, 'n');
        if( $nrOfPlacesStartPos !== false) {
            $nrOfPlaces = (int) substr($line, $nrOfPlacesStartPos + 1);
            $newContent .= $nrOfPlaces . ' => [ ' . PHP_EOL;
            continue;
        }

        $nrOfPlacesEndPos = strpos($line, 'e');
        if( $nrOfPlacesEndPos !== false) {
            $newContent .= '],' . PHP_EOL;
            continue;
        }

        $combinationsWithEmptyPlaces = explode(' ', $line);
        $combinations = array_filter( $combinationsWithEmptyPlaces, function(string $combination): bool {
            return strlen($combination) > 0;
        });
        $cyclePartNr = array_shift($combinations);
        if( $cyclePartNr !== null ) {
            $newContent .=  '  ' . $cyclePartNr . ' => [' . PHP_EOL;
            foreach( $combinations as $combination ) {
                $sides = explode(':', $combination);
                $homePlaceNrs = explode('+', $sides[0]);
                $awayPlaceNrs = explode('+', $sides[1]);
                if( count($homePlaceNrs) < 2 || count($awayPlaceNrs) < 2) {
                    continue;
                }
                $newContent .=  '      [ AgainstSide::Home->value => ['.$sides[0].'], AgainstSide::Away->value => ['.$sides[1].'] ],' . PHP_EOL;
            }
            $newContent .=  '  ],' . PHP_EOL;
        }
    }

    // Open the file for writing (creates the file if it doesn't exist)
    $fileOut = "/home/coen/projecten/php-sports-scheduler/domain/Schedules/CycleCreators/Helpers/coen2.txt";
    $handle = fopen($fileOut, "w");

    // Check if the file was successfully opened
    if ($handle) {
        // Write content to the file
        fwrite($handle, $newContent);

        // Close the file
        fclose($handle);
    }
}


//n10
// 1  4,6:5,10  2,3:7,9
//
// 6 => [
//    1 => [
//        [ AgainstSide::Home->value => [ 4, 5], AgainstSide::Away->value => [ 6, 2] ],
//    ],