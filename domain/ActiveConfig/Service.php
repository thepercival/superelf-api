<?php

declare(strict_types=1);

namespace SuperElf\ActiveConfig;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use League\Period\Period;
use Selective\Config\Configuration;
use Sports\Competition;
use Sports\Formation;
use Sports\Formation as SportsFormation;
use Sports\Formation\Line as FormationLine;
use Sports\Season;
use Sports\Sport\Custom as SportCustom;
use SuperElf\ActiveConfig;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport\Repository as SportRepository;

class Service
{
    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected SeasonRepository $seasonRepos,
        protected SportRepository $sportRepos,
        protected Configuration $config
    ) {
    }

    public function getConfig(): ActiveConfig
    {
        $activeConfig = new ActiveConfig(
            $this->getCreateAndJoinPeriod(),
            $this->getSourceCompetitions(),
        );
        $formations = [];
        /** @var string $formationName */
        foreach ($this->config->getArray('availableFormations') as $formationName) {
            $formation = new Formation();
            new FormationLine($formation, SportCustom::Football_Line_GoalKepeer, (int) substr($formationName, 0, 1));
            new FormationLine($formation, SportCustom::Football_Line_Defense, (int) substr($formationName, 2, 1));
            new FormationLine($formation, SportCustom::Football_Line_Midfield, (int) substr($formationName, 4, 1));
            new FormationLine($formation, SportCustom::Football_Line_Forward, (int) substr($formationName, 6, 1));
            $formations[] = $formation;
        }
        $activeConfig->setAvailableFormations($formations);
        return $activeConfig;
    }

    public function getCreatePeriod(): Period
    {
        $createAndJoinPeriod = $this->getCreateAndJoinPeriod();
        return new Period(
            $createAndJoinPeriod->getStartDate(),
            $createAndJoinPeriod->getEndDate()->modify('-1 days')
        );
    }

    public function getCreateAndJoinPeriod(): Period
    {
        return new Period(
            new DateTimeImmutable($this->config->getString('periods.createAndJoinStart')),
            new DateTimeImmutable($this->config->getString('periods.assembleEnd'))
        );
    }

    public function getAssemblePeriod(): Period
    {
        return new Period(
            new DateTimeImmutable($this->config->getString('periods.assembleStart')),
            new DateTimeImmutable($this->config->getString('periods.assembleEnd'))
        );
    }

    public function getAssembleViewPeriod(): Period
    {
        return new Period(
            new DateTimeImmutable($this->config->getString('periods.assembleEnd')),
            new DateTimeImmutable($this->config->getString('periods.transfersStart'))
        );
    }

    public function getTransferPeriod(): Period
    {
        return new Period(
            new DateTimeImmutable($this->config->getString('periods.transfersStart')),
            new DateTimeImmutable($this->config->getString('periods.transfersEnd'))
        );
    }

    public function getTransferViewPeriod(): Period
    {
        return new Period(
            new DateTimeImmutable($this->config->getString('periods.transfersEnd')),
            $this->getSeason()->getEndDateTime()
        );
    }

    /**
     * @return list<array<string, int|string|null>>
     * @throws Exception
     */
    protected function getSourceCompetitions(): array
    {
        $season = $this->getSeason();
        $sport = $this->sportRepos->findOneBy(["customId" => SportCustom::Football ]);
        if ($sport === null) {
            return [];
        }
        $competitions =  $this->competitionRepos->findExt($sport, $season->getPeriod());
        return array_values(array_map(function (Competition $competition): array {
            return ["id" => $competition->getId(), "name" => $competition->getName() ];
        }, $competitions));
    }

    public function getSeason(): Season
    {
        $period = new Period(
            $this->getAssemblePeriod()->getStartDate(),
            $this->getTransferPeriod()->getEndDate()
        );
        $season =  $this->seasonRepos->findOneByPeriod($period);
        if ($season === null) {
            throw new Exception('assembleviewperiod is not in a season', E_ERROR);
        }
        return $season;
    }

    /**
     * @param SportsFormation $sportsFormation
     * @return bool
     */
    public function isAvailable(SportsFormation $sportsFormation): bool
    {
        $name = $sportsFormation->getName();
        foreach ($this->getConfig()->getAvailableFormations() as $availableFormation) {
            if ($availableFormation->getName() === $name) {
                return true;
            }
        }
        return false;
    }
}
