<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Association;
use Sports\League;
use SportsHelpers\Identifiable;

class PoolCollection extends Identifiable
{
    protected Association $association;
    /**
     * @var Collection<int|string, Pool>
     */
    protected Collection $pools;

    protected const MIN_LENGTH_NAME = 3;
    protected const MAX_LENGTH_NAME = 20;

    public function __construct(Association $association)
    {
        $this->association = $association;
        $this->checkName($association->getName());
        $this->pools = new ArrayCollection();
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function getName(): string
    {
        return $this->getAssociation()->getName();
    }

    protected function checkName(string $name): void
    {
        if (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".self::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".self::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
    }

    public function getLeague(int $leagueNr): ?League
    {
        $name = $this->getLeagueName($leagueNr);
        $filtered = $this->getAssociation()->getLeagues()->filter(function (League $league) use ($name): bool {
            return $league->getName() === $name;
        });
        $firstFiltered = $filtered->first();
        return $firstFiltered === false ? null : $firstFiltered;
    }

    public function getLeagueName(int $competitionType): string
    {
        if ($competitionType === CompetitionType::CUP) {
            return 'beker';
        } elseif ($competitionType === CompetitionType::SUPERCUP) {
            return 'supercup';
        }
        return 'competitie';
    }

    /**
     * @return Collection<int|string, Pool>
     */
    public function getPools(): Collection
    {
        return $this->pools;
    }


    public function getLatestPool(): Pool|null
    {
        $pools = $this->getPools()->toArray();
        uasort($pools, function (Pool $poolA, Pool $poolB): int {
            return $poolA->getSeason()->getStartDateTime() < $poolB->getSeason()->getStartDateTime() ? -1 : 1;
        });
        $lastPool = reset($pools);
        return $lastPool === false ? null : $lastPool;
    }
}
