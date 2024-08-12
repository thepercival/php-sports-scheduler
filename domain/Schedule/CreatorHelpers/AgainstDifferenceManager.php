<?php

namespace SportsScheduler\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;

class AgainstDifferenceManager
{
    /**
     * @var array<int, AmountRange>
     */
    private array $amountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $againstAmountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $withAmountRange = [];
    /**
     * @var array<int, AmountRange>
     */
    private array $homeAmountRange = [];

    // private bool|null $canVariantAgainstBeEquallyAssigned = null;
    // private bool|null $canVariantWithBeEquallyAssigned = null;

    /**
     * @param int $nrOfPlaces
     * @param non-empty-array<int, AgainstH2h|AgainstGpp> $againstVariantMap
     * @param int $allowedMargin
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected int $nrOfPlaces,
        array $againstVariantMap,
        protected int $allowedMargin,
        protected LoggerInterface $logger)
    {
        $againstGppMap = $this->filterToAgainstGppMap($againstVariantMap);
        $this->initAmountRangeForSports($nrOfPlaces, $againstGppMap);
        $this->initAgainstAmountRangeForSports($nrOfPlaces, $againstGppMap);
        $this->initWithAmountRangeForSports($nrOfPlaces, $againstGppMap);
        $this->initHomeAmountRangeForSports($nrOfPlaces, $againstVariantMap);
    }



    /**
     * @param int $nrOfPlaces
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initAmountRangeForSports(int $nrOfPlaces, array $againstGppMap): void
    {
        $nrOfAmountCumulative = 0;

        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();

            $nrOfAmountSport = $againstGpp->getNrOfGamePlaces() * $nrOfSportGames;
            $nrOfAmountCumulative += $nrOfAmountSport;

            $allowedAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfAmountCumulative,
                $nrOfPlaces
            );

            $minNrAllowedToAssignToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfAmountCumulative,
                $nrOfPlaces
            );
            $maxNrAllowedToAssignToMaximumCum = $nrOfPlaces - $minNrAllowedToAssignToMinimumCum;

            $allowedMaxSport = $allowedAmountCum;
            $allowedMinSport = $allowedAmountCum;
            if( $minNrAllowedToAssignToMinimumCum > 0 ) {
               $allowedMinSport--;
            }

            if( $allowedMinSport < 0 ) {
                $allowedMinSport = 0;
                $minNrAllowedToAssignToMinimumCum = 0;
            }

            $this->amountRange[$sportNr] = new AmountRange(
                new Amount( $allowedMinSport, $minNrAllowedToAssignToMinimumCum),
                new Amount( $allowedMaxSport, $maxNrAllowedToAssignToMaximumCum)
            );
        }
    }

    /**
     * @param int $nrOfPlaces
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initAgainstAmountRangeForSports(int $nrOfPlaces, array $againstGppMap): void
    {
        $nrOfAgainstCombinationsCumulative = 0;

        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();

            $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
            $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;

            $allowedAgainstAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithNrOfPlaces->getNrOfPossibleAgainstCombinations()
            );

            $minNrOfAgainstAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfAgainstCombinationsCumulative,
                $againstGppWithNrOfPlaces->getNrOfPossibleAgainstCombinations()
            );
            $maxNrOfAgainstAllowedToAssignedToMaximumCum = $againstGppWithNrOfPlaces->getNrOfPossibleAgainstCombinations() - $minNrOfAgainstAllowedToAssignedToMinimumCum;

            $allowedAgainstMaxSport = $allowedAgainstAmountCum + $this->allowedMargin;
            $allowedAgainstMinSport = $allowedAgainstAmountCum - $this->allowedMargin;
            if( $this->allowedMargin > 0 ) {
                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
            } else if( $minNrOfAgainstAllowedToAssignedToMinimumCum > 0 ) {
                $allowedAgainstMinSport--;
            }

            if( $allowedAgainstMinSport < 0 ) {
                $allowedAgainstMinSport = 0;
                $minNrOfAgainstAllowedToAssignedToMinimumCum = 0;
            }

            $this->againstAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedAgainstMinSport, $minNrOfAgainstAllowedToAssignedToMinimumCum),
                new Amount( $allowedAgainstMaxSport, $maxNrOfAgainstAllowedToAssignedToMaximumCum)
            );
        }
    }

    /**
     * @param int $nrOfPlaces
     * @param array<int, AgainstGpp> $againstGppMap
     * @return void
     */
    private function initWithAmountRangeForSports(int $nrOfPlaces, array $againstGppMap): void
    {
        $totalNrOfGames = $this->calculateAgainstTotalNrOfGames($nrOfPlaces, array_values($againstGppMap));
        $nrOfAgainstVariants = count($againstGppMap);
      //  $totalNrOfGames = $this->getTotalNrOfGames($poule, $againstGppMap);

        // $allowedMarginCumulative = 0;
        $nrOfWithCombinationsCumulative = 0;

//        $counter = 0;
        foreach ($againstGppMap as $sportNr => $againstGpp) {
            $againstGppWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();
            //$lastSportVariant = ++$counter === count($againstGppMap);

//            if ($this->allowedMargin === 0) { // alle 1 en de laatste 0
//                $allowedMarginCumulative = $lastSportVariant ? 0 : 1;
//                // @TODO CDK
//                //            if( $lastSportVariant && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                $allowedMarginCumulative++;
////            }
//
////                if( $allowedMarginCumulative === 0 && !$againstGppsWithPoule->allAgainstSameNrOfGamesAssignable() ) {
////                    $allowedMarginCumulative = 1;
////                }
//            } else {
//                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $totalNrOfGames * $this->allowedMargin);
//                $allowedMarginCumulative += $allowedAgainstMarginSport;
//            }

            if( $againstGpp->hasMultipleSidePlaces()) {

                if( $againstGpp->getNrOfHomePlaces() > 2 || $againstGpp->getNrOfAwayPlaces() > 2) {
                    throw new \Exception('Only 2 NrOfWithPlaces ALLOWED');
                }

                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;

                $allowedWithAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithNrOfPlaces->getNrOfPossibleWithCombinations()
                );

                $minNrOfWithAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithNrOfPlaces->getNrOfPossibleWithCombinations()
                );
                $maxNrOfWithAllowedToAssignedToMaximumCum = $againstGppWithNrOfPlaces->getNrOfPossibleWithCombinations() - $minNrOfWithAllowedToAssignedToMinimumCum;
            } else {
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
                $maxNrOfWithAllowedToAssignedToMaximumCum = 0;
                $allowedWithAmountCum = 0;
            }

            $allowedWithMaxSport = $allowedWithAmountCum + $this->allowedMargin;
            $allowedWithMinSport = $allowedWithAmountCum - $this->allowedMargin;
            if( $this->allowedMargin > 0 ) {
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
            } else if( $minNrOfWithAllowedToAssignedToMinimumCum > 0 ) {
                $allowedWithMinSport--;
            }

            if( $allowedWithMinSport < 0 ) {
                $allowedWithMinSport = 0;
                $minNrOfWithAllowedToAssignedToMinimumCum = 0;
            }

            $this->withAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedWithMinSport, $minNrOfWithAllowedToAssignedToMinimumCum),
                new Amount( $allowedWithMaxSport, $maxNrOfWithAllowedToAssignedToMaximumCum)
            );
        }
    }

    /**
     * @param int $nrOfPlaces
     * @param non-empty-array<int, AgainstH2h|AgainstGpp> $againstVariantsMap
     * @return void
     */
    private function initHomeAmountRangeForSports(int $nrOfPlaces, array $againstVariantsMap): void
    {
        $againstVariants = array_values($againstVariantsMap);
        $totalNrOfGames = $this->calculateAgainstTotalNrOfGames($nrOfPlaces, $againstVariants);
        $nrOfAgainstVariants = count($againstVariantsMap);
        $allowedMarginCumulative = 0;
        $nrOfHomePlacesCumulative = 0;
        $againstVariantsCumulative = [];

        $counter = 0;
        foreach ($againstVariantsMap as $sportNr => $againstVariant) {

//            $againstVariant = $againstVariantWithNr->sportVariant;
//            if( !($againstVariant instanceof AgainstH2h ) && !($againstVariant instanceof AgainstGpp ) ) {
//                continue;
//            }
//            $sportNr = $againstVariantWithNr->number;

            $againstVariantsCumulative[] = $againstVariant;
            if( $againstVariant instanceof AgainstH2h ) {
                $againstWithNrOfPlaces = new AgainstH2hWithNrOfPlaces($nrOfPlaces, $againstVariant);
                $allAgainstSameNrOfGamesAssignable = true;
            } else {
                $againstWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstVariant);
                $allAgainstSameNrOfGamesAssignable = $againstWithNrOfPlaces->allAgainstSameNrOfGamesAssignable();
            }

            // EXCEPTIONS BECAUSE TOO FEW
            $allowedMargin = $this->allowedMargin;
            $exceptionHomeAwayMargin = $this->calculateExceptionHomeAwayMargin($nrOfPlaces, $againstVariantsCumulative);
            if( $exceptionHomeAwayMargin !== null ) {
                if( $exceptionHomeAwayMargin > $allowedMargin ) {
                    $allowedMargin = $exceptionHomeAwayMargin;
                }
            }

            $nrOfSportGames = $againstWithNrOfPlaces->getTotalNrOfGames();
            $isLastSportVariant = (++$counter === $nrOfAgainstVariants);

            // als alle
            if ($allowedMargin === 0) { // alle 1 en de laatste 0
                $allowedMarginCumulative = $isLastSportVariant ? 0 : 1;
                // @TODO CDK
                if( $isLastSportVariant && !$allAgainstSameNrOfGamesAssignable ) {
                    $allowedMarginCumulative++;
                }

                if( $allowedMarginCumulative === 0 && !$allAgainstSameNrOfGamesAssignable ) {
                    $allowedMarginCumulative = 1;
                }
            } else {
                $allowedAgainstMarginSport = (int)ceil($nrOfSportGames / $totalNrOfGames * $allowedMargin);
                $allowedMarginCumulative += $allowedAgainstMarginSport;
            }

            // $nrOfHomeCombinations = 1;
            $nrOfHomePlacesSport = $againstVariant->getNrOfHomePlaces() * $nrOfSportGames ;
            $nrOfHomePlacesCumulative += $nrOfHomePlacesSport;
            $allowedHomeAmountCum = (new EquallyAssignCalculator())->getMaxAmount(
                $nrOfHomePlacesCumulative,
                $nrOfPlaces /*$againstWithPoule->getNrOfPossibleWithCombinations(Side::Home)*/
            );

            $minNrOfHomeAllowedToAssignedToMinimumCum = (new EquallyAssignCalculator())->getNrOfDeficit(
                $nrOfHomePlacesCumulative,
                $nrOfPlaces
            );
            // $maxNrOfHomeAllowedToAssignedToMinimumCum = $againstWithPoule->getNrOfPossibleWithCombinations(Side::Home) - $minNrOfHomeAllowedToAssignedToMinimumCum;
            $maxNrOfHomeAllowedToAssignedToMinimumCum = $nrOfPlaces - $minNrOfHomeAllowedToAssignedToMinimumCum;

            $allowedHomeMaxSport = $allowedHomeAmountCum + $allowedMargin;
            $allowedHomeMinSport = $allowedHomeAmountCum - $allowedMargin;
//            if( $allowedMargin > 0 ) {
//                $minNrOfHomeAllowedToAssignedToMinimumCum = 0;
//            }  else if( $minNrOfHomeAllowedToAssignedToMinimumCum > 0 ) {
//                $allowedHomeMinSport--;
//            }

            if( $allowedHomeMinSport < 0 ) {
                $allowedHomeMinSport = 0;
                $minNrOfHomeAllowedToAssignedToMinimumCum = 0;
            }

            $this->homeAmountRange[$sportNr] = new AmountRange(
                new Amount( $allowedHomeMinSport, $minNrOfHomeAllowedToAssignedToMinimumCum),
                new Amount( $allowedHomeMaxSport, $maxNrOfHomeAllowedToAssignedToMinimumCum)
            );
        }
    }

    /**
     * @param int $nrOfPlaces
     * @param list<AgainstH2h|AgainstGpp> $againstVariants
     * @return int
     */
    private function calculateAgainstTotalNrOfGames(int $nrOfPlaces, array $againstVariants): int {

        $pouleStructure = new PouleStructure($nrOfPlaces);
        return $pouleStructure->getTotalNrOfGames($againstVariants);
    }

    /**
     * @param array<int, AgainstGpp|AgainstH2h> $againstVariantMap
     * @return array<int, AgainstGpp>
     */
    protected function filterToAgainstGppMap(array $againstVariantMap): array
    {
        $againstGppMap = [];
        foreach( $againstVariantMap as $sportNr => $againstVariant) {
            if( $againstVariant instanceof AgainstGpp) {
                $againstGppMap[$sportNr] = $againstVariant;
            }
        }
        return $againstGppMap;
    }


//    /**
//     * @param Poule $poule
//     * @param list<AgainstGpp> $againstGppVariants
//     * @return bool
//     */
//    private function canVariantAgainstBeEquallyAssigned(Poule $poule, array $againstGppVariants): bool {
//        if( $this->canVariantAgainstBeEquallyAssigned === null ) {
//            $calculator = new EquallyAssignCalculator();
//            $this->canVariantAgainstBeEquallyAssigned = $calculator->assignAgainstSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
//        }
//        return $this->canVariantAgainstBeEquallyAssigned;
//    }

//    /**
//     * @param Poule $poule
//     * @param list<AgainstGpp> $againstGppVariants
//     * @return bool
//     */
//    private function canVariantWithBeEquallyAssigned(Poule $poule, array $againstGppVariants): bool {
//        if( $this->canVariantWithBeEquallyAssigned === null ) {
//            $calculator = new EquallyAssignCalculator();
//            $this->canVariantWithBeEquallyAssigned = $calculator->assignWithSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
//        }
//        return $this->canVariantWithBeEquallyAssigned;
//    }

    public function getAmountRange(int $sportNr): AmountRange {
        return $this->amountRange[$sportNr];
    }

    public function getAgainstRange(int $sportNr): AmountRange {
        return $this->againstAmountRange[$sportNr];
    }

    public function getWithRange(int $sportNr): AmountRange {
        return $this->withAmountRange[$sportNr];
    }

    public function getHomeRange(int $sportNr): AmountRange {
        return $this->homeAmountRange[$sportNr];
    }


    protected function createAgainstVariantWithNrOfPlaces(
        int $nrOfPlaces,
        AgainstH2h|AgainstGpp $againstVariant): AgainstH2hWithNrOfPlaces|AgainstGppWithNrOfPlaces {
        if( $againstVariant instanceof AgainstGpp) {
            return new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstVariant);
        }
        return new AgainstH2hWithNrOfPlaces($nrOfPlaces, $againstVariant);
    }

    /**
     * @param int $nrOfPlaces
     * @param list<AgainstGpp|AgainstH2h> $againstVariants
     * @return int|null
     */
    protected function calculateExceptionHomeAwayMargin(int $nrOfPlaces, array $againstVariants): int|null {
        if ( $nrOfPlaces === 4 && $this->allAgainstVariantsHave4GamePlaces($againstVariants) ) {
            $againstGpps = $this->filterAgainstGpps($againstVariants);
            if( $this->sumAgainstNrOfGamesPerPlace($againstGpps) === 2 ||
                $this->sumAgainstNrOfGamesPerPlace($againstGpps) === 3 ) {

            }
            return 1;
        }
        return null;
    }

    /**
     * @param list<AgainstGpp|AgainstH2h> $againstVariants
     * @return list<AgainstGpp>
     */
    protected function filterAgainstGpps(array $againstVariants): array {
        $againstGpps = [];
        foreach( $againstVariants as $againstVariant ) {
            if (($againstVariant instanceof AgainstGpp)) {
                $againstGpps[] = $againstVariant;
            }
        }
        return $againstGpps;
    }


    /**
     * @param list<AgainstH2h|AgainstGpp> $againstVariants
     * @return bool
     */
    public function allAgainstVariantsHave4GamePlaces(array $againstVariants): bool {
        return count(array_filter($againstVariants, function(AgainstH2h|AgainstGpp $againstVariant): bool {
                return $againstVariant->getNrOfGamePlaces() === 4;
            })) === count($againstVariants);
    }

    /**
     * @param list<AgainstGpp> $againstGpps
     * @return int
     */
    private function sumAgainstNrOfGamesPerPlace(array $againstGpps): int {
        return array_sum( array_map( function(AgainstGpp $againstGpp): int {
            return $againstGpp->getNrOfGamesPerPlace();
        }, $againstGpps));
    }
}