<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations\StatisticsCalculators;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Amounts\AmountRange;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\RangedPlaceNrCombinationCounterMap;
use SportsPlanning\Combinations\RangedPlaceNrCounterMap;
use SportsPlanning\Place;
use SportsPlanning\SportVariant\AgainstGppWithNrOfPlaces;

final class AgainstGppStatisticsCalculator extends StatisticsCalculatorAbstract
{
    protected bool $checkOnWith;

    public function __construct(
        protected AgainstGppWithNrOfPlaces $againstGppWithNrOfPlaces,
        RangedPlaceNrCombinationCounterMap $assignedHomeMap,
        int $nrOfHomeAwaysAssigned,
        // protected RangedPlaceCounterMap $assignedSportMap,
        protected RangedPlaceNrCounterMap $assignedMap,
        protected RangedPlaceNrCombinationCounterMap $assignedAgainstMap,
        protected RangedPlaceNrCombinationCounterMap $assignedWithMap,
        LoggerInterface $logger
    )
    {
        parent::__construct($assignedHomeMap,$nrOfHomeAwaysAssigned, $logger);
        $this->checkOnWith = $againstGppWithNrOfPlaces->getSportVariant()->hasMultipleSidePlaces();
    }

    public function getNrOfGamesToGo(): int {
        return $this->againstGppWithNrOfPlaces->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
    }

    #[\Override]
    public function addHomeAway(HomeAway $homeAway): self
    {
        // $assignedSportMap = $this->assignedSportMap;
        $assignedMap = $this->assignedMap;
        foreach ($homeAway->getPlaceNrs() as $placeNr) {
            // $assignedSportMap = $assignedSportMap->addPlace($place);
            $assignedMap = $assignedMap->addPlaceNr($placeNr);
        }

        $assignedAgainstMap = $this->assignedAgainstMap;
        foreach ($homeAway->getAgainstPlaceNrCombinations() as $placeNrCombination) {
            $assignedAgainstMap = $assignedAgainstMap->addPlaceNrCombination($placeNrCombination);
        }

        $assignedWithMap = $this->assignedWithMap;
        foreach ($homeAway->getWithPlaceNrCombinations() as $placeNrCombination) {
            $assignedWithMap = $assignedWithMap->addPlaceNrCombination($placeNrCombination);
        }

        $assignedHomeMap = $this->assignedHomeMap->addPlaceNrCombination($homeAway->getHome());

        return new self(
            $this->againstGppWithNrOfPlaces,
            $assignedHomeMap,
            $this->nrOfHomeAwaysAssigned + 1,
            // $assignedSportMap,
            $assignedMap,
            $assignedAgainstMap,
            $assignedWithMap,
            $this->logger
        );
    }

    #[\Override]
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
        $minAllowedAmountDifference = $this->getMinAllowedDifference($this->assignedMap->getAllowedRange());
        return $this->amountWithinMarginHelper($minAllowedAmountDifference);
    }

    private function amountWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $assignedRange = $this->assignedMap->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedMap->countAmount($minAssigned->amount + 1);
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
        if( $this->assignedMap->withinRange($nrOfPlacesGo) ) {
            return true;
        }
        return false;
    }

    public function againstWithinMarginDuring(): bool
    {
        $minAllowedAgainstDifference = $this->getMinAllowedDifference($this->assignedAgainstMap->getAllowedRange());
        return $this->againstWithinMarginHelper($minAllowedAgainstDifference);
    }

    private function againstWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $assignedRange = $this->assignedAgainstMap->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedAgainstMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceNrCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        if( $this->assignedAgainstMap->withinRange($nrOfPlaceNrCombinationsToGo) ) {
            return true;
        }
        return false;
    }

    public function withWithinMarginDuring(): bool
    {
        $minAllowedWithDifference = $this->getMinAllowedDifference($this->assignedWithMap->getAllowedRange());
        return $this->withWithinMarginHelper($minAllowedWithDifference);
    }



    public function withWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        if( !$this->checkOnWith ) {
            return true;
        }
        $assignedRange = $this->assignedWithMap->getRange();
        if( $assignedRange === null ) {
            return true;
        }
        if( $minimalAllowedDifference !== null) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedWithMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned /* && $minAssigned->count > 10*/ ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceNrCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfWithCombinationsPerGame();
        if( $this->assignedWithMap->withinRange($nrOfPlaceNrCombinationsToGo) ) {
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
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->assignedMap->getMap(), $homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->assignedHomeMap->getMap(), $homeAway);
            $leastAgainstAmountAssigned[$homeAway->getIndex()] = $this->getLeastAgainstCombinationAssigned($this->assignedAgainstMap->getMap(), $homeAway);
            $leastWithAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->assignedWithMap->getMap(), $homeAway);
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
        $totalNrOfGamesPerPlace = $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithNrOfPlaces->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamesPerPlace();
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (HomeAway $homeAway) : bool {
                foreach ($homeAway->getPlaceNrs() as $placeNr) {
                    if( $this->assignedMap->count($placeNr) + 1 > $this->assignedMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getAgainstPlaceNrCombinations() as $placeNrCombination) {
                    if( $this->assignedAgainstMap->count($placeNrCombination) + 1 > $this->assignedAgainstMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getWithPlaceNrCombinations() as $placeNrCombination) {
                    if( $this->assignedWithMap->count($placeNrCombination) + 1 > $this->assignedWithMap->getAllowedRange()->getMax()->amount ) {
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
        $allowedRange = $this->assignedMap->getAllowedRange();
        $header .= ' allowedRange : ' . ((string)$allowedRange);
        $nrOfPossiblities = $this->assignedMap->getMap()->count();
        $header .= ', belowMinimum/max : ' . $this->assignedMap->getNrOfPlacesBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceNrCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfGamePlaces();
        $header .= '/' . $nrOfPlaceNrCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= ((string)$amount)  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. ((string)$mapRange) . '/' . $this->assignedMap->getAmountDifference());
        }
    }

    public function outputAgainstTotals(string $prefix, bool $withDetails): void {
        $header = 'AgainstTotals : ';
        $allowedRange = $this->assignedAgainstMap->getAllowedRange();
        $header .= ' allowedRange : ' . ((string)$allowedRange);
        $nrOfPossiblities = count( $this->assignedAgainstMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedAgainstMap->getNrOfPlaceNrCombinationsBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceNrCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        $header .= '/' . $nrOfPlaceNrCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedAgainstMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedAgainstMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= ((string)$amount)  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. ((string)$mapRange) . '/' . $this->assignedAgainstMap->getAmountDifference());
        }

        if( !$withDetails ) {
            return;
        }
        for( $placeNr = 1 ; $placeNr <= $this->againstGppWithNrOfPlaces->getNrOfPlaces() ; $placeNr++ ) {
            $this->outputAgainstPlaceNrTotals($placeNr, $prefix . '    ');
        }
    }

    private function outputAgainstPlaceNrTotals(int $placeNr, string $prefix): void {
        $placeNrOutput = $placeNr < 10 ? '0' . $placeNr : '' . $placeNr;
        $out = $placeNrOutput . " => ";

        $nrOfPlaces = $this->againstGppWithNrOfPlaces->getNrOfPlaces();
        for( $opponentPlaceNr = 1; $opponentPlaceNr <= $nrOfPlaces; $opponentPlaceNr++) {
            if( $opponentPlaceNr <= $placeNr ) {
                $out .= '     ,';
            } else {
                $opponentNr = $opponentPlaceNr < 10 ? '0' . $opponentPlaceNr : $opponentPlaceNr;
                $placeNrCombination = new PlaceNrCombination([$placeNr, $opponentPlaceNr]);
                $out .= '' . $opponentNr . ':' . $this->getOutputAmount($placeNrCombination) . ',';
            }
        }
        $this->logger->info($prefix . $out);
    }

    private function getOutputAmount(PlaceNrCombination $placeNrCombination): string {
        return $this->assignedAgainstMap->count($placeNrCombination) . 'x';
    }

    public function outputWithTotals(string $prefix, bool $withDetails): void
    {
        $header = 'WithTotals : ';
        $allowedRange = $this->assignedWithMap->getAllowedRange();
        $header .= ' allowedRange : ' . ((string)$allowedRange);
        $nrOfPossiblities = count( $this->assignedWithMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedWithMap->getNrOfPlaceNrCombinationsBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceNrCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithNrOfPlaces->getSportVariant()->getNrOfWithCombinationsPerGame();
        $header .= '/' . $nrOfPlaceNrCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedWithMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedWithMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= ((string)$amount)  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. ((string)$mapRange) . '/' . $this->assignedWithMap->getAmountDifference());
        }

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedWithMap->getMap()->getList() as $counterIt ) {
            $line .= ((string)$counterIt->getPlaceNrCombination()) . ' ' . $counterIt->count() . 'x, ';
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
