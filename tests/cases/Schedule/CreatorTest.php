<?php

declare(strict_types=1);

namespace SportsScheduler\Tests\Schedule;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Output\PlanningOutput;
use SportsPlanning\Output\PlanningOutput\Extra;
use SportsPlanning\PlanningRefereeInfo;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleGame;
use SportsPlanning\Schedules\ScheduleGamePlace;
use SportsPlanning\Schedules\ScheduleSport;
use SportsScheduler\Planning\PlanningValidator as PlanningValidator;
use SportsPlanning\Output\ScheduleOutput;
use SportsScheduler\Schedule\ScheduleCreator as ScheduleCreator;
use SportsScheduler\TestHelper\GppMarginCalculator;
use SportsScheduler\TestHelper\PlanningCreator;

final class CreatorTest extends TestCase
{
    use PlanningCreator;
    use GppMarginCalculator;

    public function testH2hAndGpp(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
        ];
        self::expectException(Exception::class);
        $this->createInput([2], $sportVariants);
    }

    public function testMaxMargin(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        self::assertEquals(0, $maxGppMargin);
    }

    public function testGppAndGpp5Places1GamesPerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(4, $this->getNrOfGames($schedule));

//        (new ScheduleOutput($this->getLogger()))->output($schedules);

        self::assertEquals(1, $this->getNrOfGames($schedule, 1));
        self::assertEquals(1, $this->getNrOfGames($schedule, 2));
        self::assertEquals(2, $this->getNrOfGames($schedule, 3));
        self::assertEquals(2, $this->getNrOfGames($schedule, 4));
        self::assertEquals(2, $this->getNrOfGames($schedule, 5));
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LogLevel::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getNrOfGames(Schedule $schedule, int|null $placeNr = null): int
    {
        $nrOfGames = 0;
        foreach ($schedule->getScheduleSports() as $scheduleSport) {
            if ($placeNr === null) {
                $nrOfGames += count($scheduleSport->getGames());
                continue;
            }
            foreach ($scheduleSport->getGames() as $game) {
                foreach ($game->getGamePlaces() as $gamePlace) {
                    if ($gamePlace->getNumber() === $placeNr) {
                        $nrOfGames++;
                    }
                }
            }
        }
        return $nrOfGames;
    }

    public function testGppAndGpp5Places2GamesPerPlace(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(7, $this->getNrOfGames($schedule));
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test5Gpps8Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
        ];
        $input = $this->createInput([8], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(8, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(140, $this->getNrOfGames($schedule));
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test2Single5Places(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);


        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

//        (new ScheduleOutput($this->getLogger()))->output($schedules);

        foreach ($schedule->getScheduleSports() as $scheduleSport) {
            if ($scheduleSport->getNumber() === 1) {
                $this->checkFirstGamePlace($scheduleSport, 1);
            }
            if ($scheduleSport->getNumber() === 2) {
                $this->checkFirstGamePlace($scheduleSport, 5);
            }
        }

    }

    protected function checkFirstGamePlace(ScheduleSport $scheduleSport, int $placeNr): void
    {
        $firstGame = $scheduleSport->getGames()->first();
        self::assertNotFalse($firstGame);
        $firstGamePlace = $firstGame->getGamePlaces()->first();
        self::assertNotFalse($firstGamePlace);
        self::assertEquals($placeNr, $firstGamePlace->getNumber());
    }

    public function test3Single5Places(): void
    {
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $this->getMaxGppMargin(5, $sportVariants, $this->getLogger() );
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, $maxGppMargin);

        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        foreach ($schedule->getScheduleSports() as $scheduleSport) {
            if ($scheduleSport->getNumber() === 1) {
                $this->checkFirstGamePlace($scheduleSport, 1);
            }
            if ($scheduleSport->getNumber() === 2) {
                $this->checkFirstGamePlace($scheduleSport, 5);
            }
            if ($scheduleSport->getNumber() === 3) {
                $this->checkFirstGamePlace($scheduleSport, 4);
            }
        }
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test3SportsEqualNrOfAgainst(): void
    {
        $nrOfPlaces = 4;
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1)
        ];
        $input = $this->createInput([$nrOfPlaces], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, 0);

        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        // (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        foreach( $schedules as $schedule) {
            $sportVariants = $schedule->createSportVariants();
            $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
            foreach( $schedule->getScheduleSports() as $scheduleSport) {
                $sportVariant = $scheduleSport->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $scheduleSport->createHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertSame(0, $assignedCounter->getAgainstAmountDifference() );
        }
    }

    public function test4SportsWith6Places(): void
    {
        $nrOfPlaces = 6;
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1)
        ];
        $input = $this->createInput([$nrOfPlaces], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, 1);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        foreach( $schedules as $schedule) {
            $sportVariants = $schedule->createSportVariants();
            $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
            foreach( $schedule->getScheduleSports() as $scheduleSport) {
                $sportVariant = $scheduleSport->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $scheduleSport->createHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertSame(2, $assignedCounter->getAgainstAmountDifference() );
        }
    }

    public function test14PlacesWithMultipleSportsSameNrOfHomeGames(): void
    {
        $nrOfPlaces = 14;
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2)
        ];
        $input = $this->createInput([$nrOfPlaces], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, 0);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);


        foreach( $schedules as $schedule) {
            $sportVariants = $schedule->createSportVariants();
            $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
            foreach( $schedule->getScheduleSports() as $scheduleSport) {
                $sportVariant = $scheduleSport->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $scheduleSport->createHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertSame(0, $assignedCounter->getHomeAmountDifference() );
        }
    }

    public function test12PlacesWith2VS2With8GamesPerPlaceAnd1VS1With1GamePerPlace(): void
    {
        $nrOfPlaces = 14;
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 8),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
        ];
        $input = $this->createInput([$nrOfPlaces], $sportVariantsWithFields);
        $sportVariants = array_map(function(SportVariantWithFields $sportVariantWithFields) {
            return $sportVariantWithFields->getSportVariant();
        }, $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $pouleStructure = $input->createPouleStructure();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sportVariants);
        $schedules = $scheduleCreator->createFromPouleStructureAndSports($pouleStructure, $sportVariantsWithNr, 1);
    //        (new ScheduleOutput($this->getLogger()))->output($schedules);
    //        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        foreach( $schedules as $schedule) {
            $sportVariants = $schedule->createSportVariants();
            $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
            foreach( $schedule->getScheduleSports() as $scheduleSport) {
                $sportVariant = $scheduleSport->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $scheduleSport->createHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertTrue($assignedCounter->getAgainstAmountDifference() <= 2 );
            self::assertTrue($assignedCounter->getHomeAmountDifference() <= 1 );
        }
    }

    // [8,4] - [against(1vs1) h2h:gpp=>0:2 f(2) & against(1vs1) h2h:gpp=>0:2 f(2)] - ref=>0:
    public function test84Gpp2SportsUnequalNrOfHomeGames(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(4, 4);
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2,1,1, 2),
            $this->getAgainstGppSportVariantWithFields(2,1,1, 2)
        ];
        $input = $this->createInput(
            [8,4],
            $sportVariantsWithFields,
            new PlanningRefereeInfo(0)
        );
        $planning = $this->createPlanning(
            $input,
            $nrOfGamesPerBatchRange,
            0,
            true
        );
//
//        // 6 games x 5 sports = 30 games / 5 = 6 batches
//        self::assertLessThan(12, $planning->getNrOfBatches());

        $extras = Extra::Input->value + Extra::Games->value + Extra::Totals->value +
            Extra::NrOfBatchGamesRange->value + Extra::MaxNrOfGamesInARow->value;
        (new PlanningOutput($this->getLogger()))->output($planning, $extras);

//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
    }


//    protected function getWithAssignedDifference(ScheduleSport $scheduleSport): int
//    {
//        $assignedCounter = new AssignedCounter($scheduleSport->getSchedule()->getPoule(),[$scheduleSport->createVariant()]);
//        $homeAways = $scheduleSport->convertGamesToHomeAways();
//        $assignedCounter->assignHomeAways($homeAways);
//        return $assignedCounter->getWithAmountDifference();
//    }


    protected function checkNotParticipating(ScheduleSport $scheduleSport, int $placeNr): void
    {
        self::assertCount(
            0,
            $scheduleSport->getGames()->filter(function (ScheduleGame $game) use ($placeNr): bool {
                return $game->getGamePlaces()->filter(function (ScheduleGamePlace $gamePlace) use ($placeNr): bool {
                        return $gamePlace->getNumber() === $placeNr;
                    })->count() > 0;
            })
        );
    }
}
