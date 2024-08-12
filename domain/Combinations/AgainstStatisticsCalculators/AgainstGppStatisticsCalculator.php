<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\AgainstStatisticsCalculators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\RangedDuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;

class AgainstGppStatisticsCalculator extends StatisticsCalculatorAbstract
{
    protected bool $checkOnWith;

    public function __construct(
        protected AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces,
        RangedPlaceNrCounterMap $rangedHomeNrCounterMap,
        int $nrOfHomeAwaysAssigned,
        protected RangedPlaceNrCounterMap $rangedAmountNrCounterMap,
        protected RangedDuoPlaceNrCounterMap $rangedAgainstNrCounterMap,
        protected RangedDuoPlaceNrCounterMap $rangedWithNrCounterMap,
        LoggerInterface $logger
    )
    {
        parent::__construct($rangedHomeNrCounterMap,$nrOfHomeAwaysAssigned, $logger);
        $this->checkOnWith = $againstGppWithNrOfPlaces->getSportVariant()->hasMultipleSidePlaces();
    }

    public function getNrOfGamesToGo(): int {
        return $this->againstGppWithNrOfPlaces->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): self
    {
        $rangedAmountNrCounterMap = clone $this->rangedAmountNrCounterMap;
        $rangedAmountNrCounterMap->addHomeAway($homeAway);

        $rangedAgainstNrCounterMap = clone $this->rangedAgainstNrCounterMap;
        $rangedAgainstNrCounterMap->addHomeAway($homeAway);

        $rangedWithNrCounterMap = clone $this->rangedWithNrCounterMap;
        $rangedWithNrCounterMap->addHomeAway($homeAway);

        $rangedHomeNrCounterMap = clone $this->rangedHomeNrCounterMap;
        $rangedHomeNrCounterMap->addHomeAway($homeAway);

        return new self(
            $this->againstGppWithNrOfPlaces,
            $rangedHomeNrCounterMap,
            $this->nrOfHomeAwaysAssigned + 1,
            $rangedAmountNrCounterMap,
            $rangedAgainstNrCounterMap,
            $rangedWithNrCounterMap,
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

    public function isHomeAwayAssignable(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool {
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
        $minAllowedAmountDifference = $this->getMinAllowedDifference($this->rangedAmountNrCounterMap->getAllowedRange());
        return $this->amountWithinMarginHelper($minAllowedAmountDifference);
    }

    private function amountWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $rangedAmountCounterReport = $this->rangedAmountNrCounterMap->calculateReport();
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
                $nextAssigned = $this->rangedAmountNrCounterMap->countAmount($minAssigned->amount + 1);
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
        if( $this->rangedAmountNrCounterMap->withinRange($nrOfPlacesGo) ) {
            return true;
        }
        return false;
    }

    public function againstWithinMarginDuring(): bool
    {
        $minAllowedAgainstDifference = $this->getMinAllowedDifference($this->rangedAgainstNrCounterMap->getAllowedRange());
        return $this->againstWithinMarginHelper($minAllowedAgainstDifference);
    }

    private function againstWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $rangedAgainstCounterReport = $this->rangedAgainstNrCounterMap->calculateReport();
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
                $nextAssigned = $this->rangedAgainstNrCounterMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        if( $this->rangedAgainstNrCounterMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return true;
        }
        return false;
    }

    public function withWithinMarginDuring(): bool
    {
        $minAllowedWithDifference = $this->getMinAllowedDifference($this->rangedWithNrCounterMap->getAllowedRange());
        return $this->withWithinMarginHelper($minAllowedWithDifference);
    }



    public function withWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        if( !$this->checkOnWith ) {
            return true;
        }
        $rangedWithCounterReport = $this->rangedWithNrCounterMap->calculateReport();
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
                $nextAssigned = $this->rangedWithNrCounterMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned /* && $minAssigned->count > 10*/ ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfWithCombinationsPerGame();
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
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function sortHomeAways(array $homeAways): array {
        // $time_start = microtime(true);

        $leastAmountAssigned = [];
        $leastAgainstAmountAssigned = [];
        $leastWithAmountAssigned = [];
        $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->rangedAmountNrCounterMap, $homeAway);
            $leastAgainstAmountAssigned[$homeAway->getIndex()] = $this->getLeastAgainstCombinationAssigned($this->rangedAgainstNrCounterMap, $homeAway);
            $leastWithAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->rangedWithNrCounterMap, $homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->rangedHomeNrCounterMap, $homeAway);
        }
        uasort($homeAways, function (
            OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAwayA,
            OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAwayB
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
        $totalNrOfGamesPerPlace = $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithNrOfPlaces->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamesPerPlace();
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway) : bool {
                foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
                    if( $this->rangedAmountNrCounterMap->count($placeNr) + 1 > $this->rangedAmountNrCounterMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->createAgainstDuoPlaceNrs() as $duoPlaceNr) {
                    if( $this->rangedAgainstNrCounterMap->count($duoPlaceNr) + 1 > $this->rangedAgainstNrCounterMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                if( !($homeAway instanceof OneVsOneHomeAway) ) {
                    foreach( $homeAway->createWithDuoPlaceNrs() as $duoPlaceNr) {
                        if( $this->rangedWithNrCounterMap->count($duoPlaceNr) + 1 > $this->rangedWithNrCounterMap->getAllowedRange()->getMax()->amount ) {
                            return false;
                        }
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
        $header .= '/' . $this->againstGppWithNrOfPlaces->getTotalNrOfGames();
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAssignedTotals($prefix, $withDetails);
        $this->outputAgainstTotals($prefix, $withDetails);
        $this->outputWithTotals($prefix, $withDetails);
        $this->outputHomeTotals($prefix, $withDetails);
    }

    public function outputAssignedTotals(string $prefix, bool $withDetails): void {
        $header = 'AssignedTotals : ';
        $allowedRange = $this->rangedAmountNrCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedAmountCounterReport = $this->rangedAmountNrCounterMap->calculateReport();
        $nrOfPossiblities = $rangedAmountCounterReport->getNOfPossibleCombinations();
        $header .= ', belowMinimum(total) : ' . $rangedAmountCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamePlaces();
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
        $allowedRange = $this->rangedAgainstNrCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedAgainstCounterReport = $this->rangedAgainstNrCounterMap->calculateReport();
        $nrOfPossiblities = $rangedAgainstCounterReport->getNrOfPossibleCombinations();
        $rangedAgainstCounterReport = $this->rangedAgainstNrCounterMap->calculateReport();
        $header .= ', belowMinimum(total) : ' . $rangedAgainstCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfAgainstCombinationsPerGame();
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
                $duoPlaceNr = new DuoPlaceNr($placeNr, $opponentNr);
                $out .= '' . $opponentNr . ':' . $this->getOutputAmount($duoPlaceNr) . ',';
            }
        }
        $this->logger->info($prefix . $out);
    }

    private function getOutputAmount(DuoPlaceNr $duoPlaceNr): string {
        return $this->rangedAgainstNrCounterMap->count($duoPlaceNr) . 'x';
    }

    public function outputWithTotals(string $prefix, bool $withDetails): void
    {
        $header = 'WithTotals : ';
        $allowedRange = $this->rangedWithNrCounterMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $rangedWithCounterReport = $this->rangedWithNrCounterMap->calculateReport();
        $nrOfPossiblities = $rangedWithCounterReport->getNrOfPossibleCombinations();
        $header .= ', belowMinimum(total)) : ' . $rangedWithCounterReport->getTotalBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfWithCombinationsPerGame();
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
        foreach( $this->rangedWithNrCounterMap->copyDuoPlaceNrCounters() as $duoPlaceNrCounter ) {
            $line .= $duoPlaceNrCounter . ', ';
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
