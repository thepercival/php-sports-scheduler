<?php

declare(strict_types=1);

namespace SportsScheduler\TestHelper;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\EquallyAssignCalculator;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsPlanning\Poule;
use stdClass;

trait GppMarginCalculator
{
    protected function calculateMaxGppMargin(Poule $poule): int {
        $sportVariants = $poule->getInput()->createSportVariants();

        $nrOfPlaces = count($poule->getPlaces());

        $maxAgainstMargin = 0;
        $maxWithMargin = 0;

        // AgainstGpp
        {
            $againstGpps = $this->filterAgainstGpps($sportVariants);
            if( count($againstGpps) > 0 ) {
                $margins = $this->calculateMaxWithAndAgainstMargin($nrOfPlaces, $againstGpps);
                /** @var int $maxWithMargin */
                $maxWithMargin = $margins->maxWithMargin;
                /** @var int $maxAgainstMargin */
                $maxAgainstMargin = $margins->maxAgainstMargin;
            }
        }

        // Single
        {
            $singleVariants = $this->filterSingleVariants($sportVariants);
            if( count($singleVariants) > 0 ) {
                $maxWithMargin = max(1, $maxWithMargin);
            }
        }

        return max($maxAgainstMargin, $maxWithMargin);
    }

    /**
     * @param list<AllInOneGame|Single|AgainstGpp|AgainstH2h> $sportVariants
     * @return list<Single>
     */
    private function filterSingleVariants(array $sportVariants): array {
        $singleVariants = [];
        foreach( $sportVariants as $sportVariant ) {
            if ($sportVariant instanceof Single) {
                $singleVariants[] = $sportVariant;
            }
        }
        return $singleVariants;
    }

    /**
     * @param list<AllInOneGame|Single|AgainstGpp|AgainstH2h> $sportVariants
     * @return list<AgainstGpp>
     */
    private function filterAgainstGpps(array $sportVariants): array {
        $againstGpps = [];
        foreach( $sportVariants as $sportVariant ) {
            if ($sportVariant instanceof AgainstGpp) {
                $againstGpps[] = $sportVariant;
            }
        }
        return $againstGpps;
    }

    /**
     * @param int $nrOfPlaces
     * @param list<AgainstGpp> $againstGpps
     * @return stdClass
     */
    private function calculateMaxWithAndAgainstMargin(int $nrOfPlaces, array $againstGpps): stdClass {
        $allowedAgainstAmountCum = 0;
        $nrOfAgainstCombinationsCumulative = 0;
        $allowedWithAmountCum = 0;
        $nrOfWithCombinationsCumulative = 0;
        foreach ($againstGpps as $againstGpp) {

            $againstGppWithNrOfPlaces = new AgainstGppWithNrOfPlaces($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithNrOfPlaces->getTotalNrOfGames();
            // against
            {
                $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
                $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;
                $allowedAgainstAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfAgainstCombinationsCumulative,
                    $againstGppWithNrOfPlaces->getNrOfPossibleAgainstCombinations()
                );
            }
            // with
            {
                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;
                $allowedWithAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithNrOfPlaces->getNrOfPossibleWithCombinations()
                );
            }
        }
        $margins = new stdClass();
        $margins->maxWithMargin = $allowedAgainstAmountCum;
        $margins->maxAgainstMargin = $allowedWithAmountCum;
        return $margins;
    }
}
