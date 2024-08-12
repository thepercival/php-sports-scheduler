<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceCounterMap;
use SportsScheduler\Combinations\StatisticsCalculator;
use SportsScheduler\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\Place;
// use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;

class GamesPerPlace extends StatisticsCalculator
{
    protected bool $checkOnWith;

    public function __construct(
        protected AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces,
        RangedPlaceCounterMap $rangedHomeCounterMap,
        int $nrOfHomeAwaysAssigned,
        protected RangedPlaceCounterMap $rangedAmountCounterMap,
        protected RangedPlaceCombinationCounterMap $rangedAgainstCounterMap,
        protected RangedPlaceCombinationCounterMap $rangedWithCounterMap,
        LoggerInterface $logger
    )
    {
        parent::__construct($rangedHomeCounterMap,$nrOfHomeAwaysAssigned, $logger);
        $this->checkOnWith = $againstGppWithNrOfPlaces->getSportVariant()->hasMultipleSidePlaces();
    }

    public function getNrOfGamesToGo(): int {
        return $this->againstGppWithNrOfPlaces->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        $rangedAmountCounterMap = clone $this->rangedAmountCounterMap;
        $rangedAmountCounterMap->addHomeAway($homeAway);

        $rangedAgainstCounterMap = clone $this->rangedAgainstCounterMap;
        $rangedAgainstCounterMap->addHomeAway($homeAway);

        $rangedWithCounterMap = clone $this->rangedWithCounterMap;
        $rangedWithCounterMap->addHomeAway($homeAway);

        $rangedHomeCounterMap = clone $this->rangedHomeCounterMap;
        $rangedHomeCounterMap->addHomeAway($homeAway);

        return new self(
            $this->againstGppWithNrOfPlaces,
            $rangedHomeCounterMap,
            $this->nrOfHomeAwaysAssigned + 1,
            $rangedAmountCounterMap,
            $rangedAgainstCounterMap,
            $rangedWithCounterMap,
            $this->logger
        );
    }

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstGppWithNrOfPlaces->getTotalNrOfGames()) {
            return false;
        }

        if( !$this->amountWithinMarginHelper() ) {
//            $this->output();
            return false;
        }

        if( !$this->againstWithinMarginHelper() ) {
//            $this->output();
            return false;
        }

        if( !$this->withWithinMarginHelper() ) {
            return false;
        }
        return true;
    }

    public function isHomeAwayAssignable(HomeAway $homeAway): bool {
        $statisticsCalculator = $this->addHomeAway($homeAway);
        if( !$statisticsCalculator->amountWithinMarginDuring() ) {
            return false;
        }

        if( !$statisticsCalculator->againstWithinMarginDuring() ) {
            return false;
        }

        if( !$statisticsCalculator->withWithinMarginDuring() ) {
            return false;
        }
        return true;
    }

    public function amountWithinMarginDuring(): bool
    {
        $minAllowedAmountDifference = $this->getMinAllowedDifference($this->rangedAmountCounterMap->getAllowedRange());
        return $this->amountWithinMarginHelper($minAllowedAmountDifference);
    }

    private function amountWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $rangedAmountCounterReport = $this->rangedAmountCounterMap->calculateReport();
        $assignedRange = $rangedAmountCounterReport->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($rangedAmountCounterReport->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->rangedAmountCounterMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
//            if( $this->nrOfHomeAwaysAssigned > 80 && $assignedRange->getAmountDifference() > 1 ) {
//                return false;
//            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlacesGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamePlaces();
        if( $this->rangedAmountCounterMap->withinRange($nrOfPlacesGo) ) {
            return true;
        }
        return false;
    }

    public function againstWithinMarginDuring(): bool
    {
        $minAllowedAgainstDifference = $this->getMinAllowedDifference($this->rangedAgainstCounterMap->getAllowedRange());
        return $this->againstWithinMarginHelper($minAllowedAgainstDifference);
    }

    private function againstWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $rangedAgainstCounterReport = $this->rangedAgainstCounterMap->calculateReport();
        $assignedRange = $rangedAgainstCounterReport->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->rangedAgainstCounterMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        if( $this->rangedAgainstCounterMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return true;
        }
        return false;
    }

    public function withWithinMarginDuring(): bool
    {
        $minAllowedWithDifference = $this->getMinAllowedDifference($this->rangedWithCounterMap->getAllowedRange());
        return $this->withWithinMarginHelper($minAllowedWithDifference);
    }



    public function withWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        if( !$this->checkOnWith ) {
            return true;
        }
        $rangedWithCounterReport = $this->rangedWithCounterMap->calculateReport();
        $assignedRange = $rangedWithCounterReport->getRange();
        if( $assignedRange === null ) {
            return true;
        }
        if( $minimalAllowedDifference !== null) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->rangedWithCounterMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned /* && $minAssigned->count > 10*/ ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        if( $rangedWithCounterReport->withinRange($nrOfPlaceCombinationsToGo) ) {
            return true;
        }

        return false;
    }

    private function getMinAllowedDifference(AmountRange $allowedRange): int {
        if( $allowedRange->getAmountDifference() < 2 ) {
            return 2;
        }
        return $allowedRange->getAmountDifference();
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param LoggerInterface $logger
     * @return list<HomeAway>
     */
    public function sortHomeAways(array $homeAways, LoggerInterface $logger): array {
        // $time_start = microtime(true);

        $leastAmountAssigned = [];
        $leastAgainstAmountAssigned = [];
        $leastWithAmountAssigned = [];
        $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->rangedAmountCounterMap, $homeAway);
            $leastAgainstAmountAssigned[$homeAway->getIndex()] = $this->getLeastAgainstCombinationAssigned($this->rangedAgainstCounterMap, $homeAway);
            $leastWithAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->rangedWithCounterMap, $homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->rangedHomeCounterMap, $homeAway);
        }
        uasort($homeAways, function (
            HomeAway $homeAwayA,
            HomeAway $homeAwayB
        ) use($leastAmountAssigned, $leastAgainstAmountAssigned, $leastWithAmountAssigned, $leastHomeAmountAssigned): int {
            $leastAmountAssignedA = $leastAmountAssigned[$homeAwayA->getIndex()];
            $leastAmountAssignedB = $leastAmountAssigned[$homeAwayB->getIndex()];
            if ($leastAmountAssignedA->amount !== $leastAmountAssignedB->amount) {
                return $leastAmountAssignedA->amount - $leastAmountAssignedB->amount;
            } else if ($leastAmountAssignedA->nrOfPlaces !== $leastAmountAssignedB->nrOfPlaces) {
                return $leastAmountAssignedB->nrOfPlaces - $leastAmountAssignedA->nrOfPlaces;
            }

            // if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                $leastAmountAssignedAgainstA = $leastAgainstAmountAssigned[$homeAwayA->getIndex()];
                $leastAmountAssignedAgainstB = $leastAgainstAmountAssigned[$homeAwayB->getIndex()];
                if ($leastAmountAssignedAgainstA->amount !== $leastAmountAssignedAgainstB->amount) {
                    return $leastAmountAssignedAgainstA->amount - $leastAmountAssignedAgainstB->amount;
                } else if ($leastAmountAssignedAgainstA->nrOfPlaces !== $leastAmountAssignedAgainstB->nrOfPlaces) {
                    return $leastAmountAssignedAgainstB->nrOfPlaces - $leastAmountAssignedAgainstA->nrOfPlaces;
                }
            // }

            // if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                $leastAmountAssignedWithA = $leastWithAmountAssigned[$homeAwayA->getIndex()];
                $leastAmountAssignedWithB = $leastWithAmountAssigned[$homeAwayB->getIndex()];
                if ($leastAmountAssignedWithA->amount !== $leastAmountAssignedWithB->amount) {
                    return $leastAmountAssignedWithA->amount - $leastAmountAssignedWithB->amount;
                } else if ($leastAmountAssignedWithA->nrOfPlaces !== $leastAmountAssignedWithB->nrOfPlaces) {
                    return $leastAmountAssignedWithB->nrOfPlaces - $leastAmountAssignedWithA->nrOfPlaces;
                }
            // }

            $leastAmountAssignedHomeA = $leastHomeAmountAssigned[$homeAwayA->getIndex()];
            $leastAmountAssignedHomeB = $leastHomeAmountAssigned[$homeAwayB->getIndex()];

            if ($leastAmountAssignedHomeA->amount !== $leastAmountAssignedHomeB->amount) {
                return $leastAmountAssignedHomeA->amount - $leastAmountAssignedHomeB->amount;
            }
            return $leastAmountAssignedHomeA->nrOfPlaces - $leastAmountAssignedHomeB->nrOfPlaces;
            // return 0;
        });
        // $logger->info("sorted homeaways in " . (microtime(true) - $time_start));
        // (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
        return array_values($homeAways);
    }

//    public function minimalSportCanStillBeAssigned(): bool {
//        // HIER OOK MEENEMEN DAT JE NOG EEN X AANTAL SPEELRONDEN HEBT,
//        // WAARDOOR SOMMIGE PLEKKEN OOK NIET MEER KUNNEN
//        // BETER IS NOG OM HET VERSCHIL NIET GROTER DAN 1 TE LATEN ZIJN,
//        // DAN HOEF JE AAN HET EIND OOK NIET MEER TE CONTROLEREN
//        // EERST DUS EEN RANGEDPLACECOUNTERMAP MAKEN
//        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
//        $minNrOfGamesPerPlace = $this->getMinNrOfGamesPerPlace();
//
//        foreach( $this->againstGppWithPoule->getPoule()->getPlaces() as $place ) {
//            if( ($this->assignedSportMap->count($place) + $nrOfGamesToGo) < $minNrOfGamesPerPlace ) {
//                return false;
//            }
//        }
//        return true;
//    }

//    public function sportWillBeOverAssigned(Place $place): bool
//    {
//        return $this->assignedSportMap->count($place) >= $this->getMaxNrOfGamesPerPlace();
//    }

    private function getMinNrOfGamesPerPlace(): int {
        $totalNrOfGamesPerPlace = $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (HomeAway $homeAway) : bool {
                foreach ($homeAway->getPlaces() as $place) {
                    if( $this->rangedAmountCounterMap->count($place) + 1 > $this->rangedAmountCounterMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getAgainstPlaceCombinations() as $placeCombination) {
                    if( $this->rangedAgainstCounterMap->count($placeCombination) + 1 > $this->rangedAgainstCounterMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getWithPlaceCombinations() as $placeCombination) {
                    if( $this->rangedWithCounterMap->count($placeCombination) + 1 > $this->rangedWithCounterMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }

//                $statisticsCalculator = $this->addHomeAway($homeAway);
//                if( !$statisticsCalculator->amountWithinMargin() ) {
//                    return false;
//                }
//                if( !$statisticsCalculator->againstWithinMargin() ) {
//                    return false;
//                }
//                if( !$statisticsCalculator->withWithinMargin() ) {
//                    return false;
//                }
                 return true;
            }
        );
        return array_values($homeAways);
    }

    public function output(bool $withDetails): void {
        $header = 'nrOfHomeAwaysAssigned/max: ' . $this->nrOfHomeAwaysAssigned;
        $header .= '/' . $this->againstGppWithPoule->getTotalNrOfGames();
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAssignedTotals($prefix, $withDetails);
        $this->outputAgainstTotals($prefix, $withDetails);
        $this->outputWithTotals($prefix, $withDetails);
        $this->outputHomeTotals($prefix, $withDetails);
    }

    public function outputAssignedTotals(string $prefix, bool $withDetails): void {
        $header = 'AssignedTotals : ';
        $allowedRange = $this->rangedAmountCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedAmountCounterReport = $this->rangedAmountCounterMap->calculateReport();
        $nrOfPossiblities = $rangedAmountCounterReport->getNOfPossibleCombinations();
        $header .= ', belowMinimum(total) : ' . $rangedAmountCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfGamePlaces();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $rangedAmountCounterReport->getRange();
        if( $mapRange !== null ) {
            $map = $rangedAmountCounterReport->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $rangedAmountCounterReport->getAmountDifference());
        }
    }

    public function outputAgainstTotals(string $prefix, bool $withDetails): void {
        $header = 'AgainstTotals : ';
        $allowedRange = $this->rangedAgainstCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedAgainstCounterReport = $this->rangedAgainstCounterMap->calculateReport();
        $nrOfPossiblities = $rangedAgainstCounterReport->getNrOfPossibleCombinations();
        $rangedAgainstCounterReport = $this->rangedAgainstCounterMap->calculateReport();
        $header .= ', belowMinimum(total) : ' . $rangedAgainstCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $rangedAgainstCounterReport->getRange();
        if( $mapRange !== null ) {
            $map = $rangedAgainstCounterReport->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }

            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $rangedAgainstCounterReport->getAmountDifference());
        }

        if( !$withDetails ) {
            return;
        }
        for( $placeNr = 1 ; $placeNr <= $this->againstGppWithNrOfPlaces->getNrOfPlaces() ; $placeNr++ ) {
            $this->outputAgainstPlaceTotals($placeNr, $prefix . '    ');
        }
    }

    private function outputAgainstPlaceTotals(int  $placeNr, string $prefix): void {
        $placeNrOutput = $placeNr < 10 ? '0' . $placeNr : $placeNr;
        $out = $placeNrOutput . " => ";
        for( $opponentNr = 1 ; $opponentNr <= $this->againstGppWithNrOfPlaces->getNrOfPlaces() ; $opponentNr++ ) {
            if( $opponentNr <= $placeNr) {
                $out .= '     ,';
            } else {
                $opponentNrOutput = $opponentNr < 10 ? '0' . $opponentNr : $opponentNr;
                $placeCombination = new PlaceCombination([$place, $opponent]);
                $out .= '' . $opponentNr . ':' . $this->getOutputAmount($placeCombination) . ',';
            }
        }
        $this->logger->info($prefix . $out);
    }

    private function getOutputAmount(PlaceCombination $placeCombination): string {
        return $this->rangedAgainstCounterMap->count($placeCombination) . 'x';
    }

    public function outputWithTotals(string $prefix, bool $withDetails): void
    {
        $header = 'WithTotals : ';
        $allowedRange = $this->rangedWithCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedWithCounterReport = $this->rangedWithCounterMap->calculateReport();
        $nrOfPossiblities = $rangedWithCounterReport->getNrOfPossibleCombinations();
        $header .= ', belowMinimum(total)) : ' . $rangedWithCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $mapRange = $rangedWithCounterReport->getRange();
        if( $mapRange !== null ) {
            $map = $rangedWithCounterReport->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $rangedWithCounterReport->getAmountDifference());
        }

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->rangedWithCounterMap->copyPlaceCombinationCounters() as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info($prefix . $line);
        }
    }
}
