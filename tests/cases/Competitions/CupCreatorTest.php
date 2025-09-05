<?php

namespace SuperElf\Tests\Competitions;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\Output\StructureOutput;
use Sports\Ranking\PointsCalculation;
use Sports\Season;
use Sports\Sport;
use Sports\Structure;
use SuperElf\Competitions\CupCreator;
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

    public function createCupStructureForNrOfPlaces(int $nrOfQualifiers): Structure
    {
        $cupCreator = new CupCreator();

        $season = new Season(
            "20/21",
            new Period(new DateTimeImmutable(), (new DateTimeImmutable())->modify("+1 year"))
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

        return $cupCreator->createStructure($poolCup, $nrOfQualifiers);
    }
}
