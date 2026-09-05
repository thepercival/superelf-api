<?php

namespace SuperElf\Tests\Competitions;

use DateTimeImmutable;
use League\Period\Period as LeaguePeriod;
use Sports\Association;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Output\StructureOutput;
use Sports\Ranking\PointsCalculation;
use Sports\Season;
use Sports\Sport;
use Sports\Structure;
use SuperElf\Competitions\CupCreator;
use SuperElf\GameRound;
use PHPUnit\Framework\TestCase;
use SuperElf\League;
use SuperElf\League as S11League;
use SuperElf\PoolCollection;
use SuperElf\Pool;
use SuperElf\TestHelpers\Creator;

final class CupCreatorTest extends TestCase
{
    use Creator;

    public function testCup2Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(2);

        // (new StructureOutput())->output($structure);
        self::assertSame(2, $structure->getFirstRoundNumber()->getNrOfPlaces());
    }

    public function testCup3Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(3);

        // (new StructureOutput())->output($structure);
        self::assertSame(3, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup4Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(4);

        //(new StructureOutput())->output($structure);
        self::assertSame(4, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup5Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(5);

        //(new StructureOutput())->output($structure);
        self::assertSame(5, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(4, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(3, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup8Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(8);

        //(new StructureOutput())->output($structure);
        self::assertSame(8, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(4, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(3, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup9Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(9);

        // (new StructureOutput())->output($structure);
        self::assertSame(9, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(8, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        $poules = $structure->getFirstRoundNumber()->getNext()?->getPoules();
        self::assertSame(4, $poules === null ? 0 : count($poules));
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(4, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup16Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(16);

        //(new StructureOutput())->output($structure);
        self::assertSame(16, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(8, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(4, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup17Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(17);

        //(new StructureOutput())->output($structure);
        self::assertSame(17, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(16, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(5, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCup31Places(): void
    {
        $structure = $this->createCupStructureForNrOfPlaces(31);

        //(new StructureOutput())->output($structure);
        self::assertSame(31, $structure->getFirstRoundNumber()->getNrOfPlaces());
        self::assertSame(16, count($structure->getFirstRoundNumber()->getPoules()));
        self::assertSame(16, $structure->getFirstRoundNumber()->getNext()?->getNrOfPlaces());
        self::assertSame(2, $structure->getLastRoundNumber()->getNrOfPlaces());
        self::assertSame(5, $structure->getLastRoundNumber()->getNumber());
    }

    public function testCreateFiveRoundsWithEighteenAssembleAndTwelveTransferGameRounds(): void
    {
        $structure = $this->createFiveRoundCupGames(18, 12);

        $this->assertCupGameRoundNumbers(
            $structure,
            [[6, 7, 8], [10, 11, 12], [14, 15, 16], [26, 27, 28], [30, 31, 32]]
        );
    }

    public function testCreateFiveRoundsKeepsThirdRoundInTransferWithoutAssembleRoom(): void
    {
        $structure = $this->createFiveRoundCupGames(12, 13);

        $this->assertCupGameRoundNumbers(
            $structure,
            [[6, 7, 8], [10, 11, 12], [23, 24, 25], [27, 28, 29], [31, 32, 33]]
        );
    }

    /**
     * @param list<list<int>> $expectedGameRoundNumbers
     */
    private function assertCupGameRoundNumbers(Structure $structure, array $expectedGameRoundNumbers): void
    {
        $round = $structure->getSingleCategory()->getRootRound();
        foreach ($expectedGameRoundNumbers as $expected) {
            $actual = [];
            foreach ($round->getGames() as $game) {
                if ($game instanceof AgainstGame) {
                    $actual[] = $game->getGameRoundNumber();
                }
            }
            $actual = array_values(array_unique($actual));
            self::assertSame($expected, $actual);
            $children = $round->getChildren();
            $nextRound = array_shift($children);
            if ($nextRound === null) {
                break;
            }
            $round = $nextRound;
        }
    }

    private function createFiveRoundCupGames(int $nrOfAssembleGameRounds, int $nrOfTransferGameRounds): Structure
    {
        [$cupCreator, $pool, $structure] = $this->createCupForNrOfPlaces(31);
        $assembleViewPeriod = $pool->getCompetitionConfig()->getAssemblePeriod()->getViewPeriod();
        for ($gameRoundNumber = 1; $gameRoundNumber <= $nrOfAssembleGameRounds; $gameRoundNumber++) {
            new GameRound($assembleViewPeriod, $gameRoundNumber);
        }
        $transferViewPeriod = $pool->getCompetitionConfig()->getTransferPeriod()->getViewPeriod();
        for ($index = 1; $index <= $nrOfTransferGameRounds; $index++) {
            new GameRound($transferViewPeriod, 22 + $index);
        }
        $cupCreator->createGames($structure, $pool);
        return $structure;
    }

    public function createCupStructureForNrOfPlaces(int $nrOfQualifiers): Structure
    {
        return $this->createCupForNrOfPlaces($nrOfQualifiers)[2];
    }

    /**
     * @return array{CupCreator, Pool, Structure}
     */
    private function createCupForNrOfPlaces(int $nrOfQualifiers): array
    {
        $cupCreator = new CupCreator();

        $season = new Season(
            "20/21",
            LeaguePeriod::fromDate(new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year"))
        );

        $competitionConfig = $this->createCompetitionConfig($this->createSourceCompetition($season));

        $familieDuim = new Association('Familie Duim');
        new \Sports\League($familieDuim, S11League::Cup->name);
        $pool = new Pool(
            new PoolCollection($familieDuim),
            $competitionConfig
        );

        $sport = $this->createSport(League::Cup);
        $poolCup = $cupCreator->createCompetition($pool, $sport, PointsCalculation::AgainstGamePoints);

        return [$cupCreator, $pool, $cupCreator->createStructure($poolCup, $nrOfQualifiers)];
    }
}
