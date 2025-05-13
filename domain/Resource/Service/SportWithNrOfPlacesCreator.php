<?php

namespace SportsScheduler\Resource\Service;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsOneWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

class SportWithNrOfPlacesCreator
{
    public function create(
        int $nrOfPlaces,
        TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sport):
    TogetherSportWithNrOfPlaces|AgainstOneVsOneWithNrOfPlaces|
    AgainstOneVsTwoWithNrOfPlaces|AgainstTwoVsTwoWithNrOfPlaces{
        if( $sport instanceof TogetherSport ) {
            return new TogetherSportWithNrOfPlaces($nrOfPlaces, $sport);
        } else if( $sport instanceof AgainstOneVsOne ) {
            return new AgainstOneVsOneWithNrOfPlaces($nrOfPlaces, $sport);
        } else if( $sport instanceof AgainstOneVsTwo ) {
            return new AgainstOneVsTwoWithNrOfPlaces($nrOfPlaces, $sport);
        } else { // if( $sport instanceof AgainstTwoVsTwo ) {
            return new AgainstTwoVsTwoWithNrOfPlaces($nrOfPlaces, $sport);
        }
    }

}