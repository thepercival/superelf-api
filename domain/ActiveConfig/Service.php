<?php

declare(strict_types=1);

namespace SuperElf\ActiveConfig;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use League\Period\Period;
use Selective\Config\Configuration;
use Sports\Competition;
use Sports\Season;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Pool\Period as PoolPeriod;
use Sports\Sport\Custom as SportCustom;
use SuperElf\ActiveConfig;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport\Repository as SportRepository;

class Service
{
    protected CompetitionRepository $competitionRepos;
    protected SeasonRepository $seasonRepos;
    protected SportRepository $sportRepos;
    protected Configuration $config;

    public function __construct(
        CompetitionRepository $competitionRepos,
        SeasonRepository $seasonRepos,
        SportRepository $sportRepos,
        Configuration $config) {
        $this->competitionRepos = $competitionRepos;
        $this->seasonRepos = $seasonRepos;
        $this->sportRepos = $sportRepos;
        $this->config = $config;
    }

    public function getConfig(): ActiveConfig {
        $activeConfig = new ActiveConfig (
            $this->getCreateAndJoinPeriod(),
            $this->getSourceCompetitions(),
        );
        $formations = [];
        /** @var string $formationName */
        foreach( $this->config->getArray('availableFormationNames' ) as $formationName ) {
            $formation = new Formation();
            new FormationLine( $formation, SportCustom::Football_Line_GoalKepeer, (int) substr( $formationName,0, 1 ) );
            new FormationLine( $formation, SportCustom::Football_Line_Defense, (int) substr( $formationName,2, 1 ) );
            new FormationLine( $formation, SportCustom::Football_Line_Midfield, (int) substr( $formationName,4, 1 ) );
            new FormationLine( $formation, SportCustom::Football_Line_Forward, (int) substr( $formationName,6, 1 ) );
            $formations[] = $formation;
        }
        $activeConfig->setAvailableFormations( $formations );
        return $activeConfig;
    }

    public function getCreateAndJoinPeriod(): Period {
        return new Period (
            $this->getSeason()->getStartDateTime(),
            new DateTimeImmutable( $this->config->getString('periods.assembleEnd' ) )
        );
    }

    public function getAssemblePeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.assembleStart' ) ),
            new DateTimeImmutable( $this->config->getString('periods.assembleEnd' ) )
        );
    }

    public function getAssembleViewPeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.assembleEnd' ) ),
            new DateTimeImmutable( $this->config->getString('periods.transfersStart' ) )
        );
    }

    public function getTransferPeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.transfersStart' ) ),
            new DateTimeImmutable( $this->config->getString('periods.transfersEnd' ) )
        );
    }

    public function getTransferViewPeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.transfersEnd' ) ),
            $this->getSeason()->getEndDateTime()
        );
    }

    protected function getSourceCompetitions(): array {
        $season = $this->getSeason();
        if( $season === null ) {
            return [];
        }
        $sport = $this->sportRepos->findOneBy( ["customId" => SportCustom::Football ] );
        if( $sport === null ) {
            return [];
        }
        $competitions =  $this->competitionRepos->findExt( $sport, $season->getPeriod() );
        return array_map( function( Competition $competition ): array {
            return ["id" => $competition->getId(), "name" => $competition->getName() ];
        }, $competitions );
    }

    public function getSeason(): ?Season {
        return $this->seasonRepos->findOneByPeriod( $this->getAssembleViewPeriod() );
    }
}
