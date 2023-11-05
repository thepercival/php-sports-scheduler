<?php

declare(strict_types=1);

namespace SportsScheduler\Combinations;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

/**
 * @template T
 */
abstract class Validator
{
    protected AgainstSportVariant $sportVariant;
    /**
     * @var array<int|string, T>
     */
    protected array $counters = [];

    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        $sportVariant = $this->sport->createVariant();
        if (!($sportVariant instanceof AgainstSportVariant)) {
            throw new \Exception('only against-sports', E_ERROR);
        }
        $this->sportVariant = $sportVariant;
    }

    public function getPlaceCombination(AgainstGame $game, AgainstSide $side): PlaceCombination
    {
        $poulePlaces = array_values( array_map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        }, $game->getSidePlaces($side)->toArray() ) );
        return new PlaceCombination($poulePlaces);
    }

    public function addGames(Planning $planning): void
    {
        foreach ($planning->getAgainstGamesForPoule($this->poule) as $game) {
            $this->addGame($game);
        }
    }

    abstract public function addGame(AgainstGame $game): void;
}
