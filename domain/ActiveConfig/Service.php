<?php

declare(strict_types=1);

namespace SuperElf\ActiveConfig;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use League\Period\Period;
use Selective\Config\Configuration;
use Sports\Competition;
use Sports\Sport\Custom as SportCustom;
use SuperElf\ActiveConfig;
use Sports\Competition\Repository as CompetitionRepository;

class Service
{
    protected CompetitionRepository $competitionRepos;
    protected Configuration $config;
    protected int $activeSport;

    public function __construct(
        CompetitionRepository $competitionRepos,
        Configuration $config) {
        $this->competitionRepos = $competitionRepos;
        $this->config = $config;
        $this->activeSport = SportCustom::Football;
    }

    public function getActiveConfig(): ActiveConfig {
        return new ActiveConfig (
            $this->getActiveCreateAndJoinPeriod(),
            $this->getActiveJoinAndChoosePlayersPeriod(),
            $this->getActiveSourceCompetitions(),
        );
    }

    public function getActiveCreateAndJoinPeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.createAndJoinStart' ) ),
            new DateTimeImmutable( $this->config->getString('periods.joinAndChoosePlayersStart' ) )
        );
    }

    public function getActiveJoinAndChoosePlayersPeriod(): Period {
        return new Period (
            new DateTimeImmutable( $this->config->getString('periods.joinAndChoosePlayersStart' ) ),
            new DateTimeImmutable( $this->config->getString('periods.joinAndChoosePlayersEnd' ) )
        );
    }

    protected function getActiveSourceCompetitions(): array {
        $activePeriod = $this->getActiveJoinAndChoosePlayersPeriod();
        $competitions =  $this->competitionRepos->findByDate( $activePeriod->getEndDate() );
        $filtered = array_filter( $competitions, function ( Competition $competition): bool {
            return $competition->getFirstSportConfig()->getSport()->getCustomId() === $this->activeSport;
        });
        return array_map( function( Competition $competition ): array {
            return ["id" => $competition->getId(), "name" => $competition->getName() ];
        }, $filtered );
    }
}
