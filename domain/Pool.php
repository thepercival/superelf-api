<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition;
use Sports\Season;
use SportsHelpers\Identifiable;
use SuperElf\League as S11League;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

class Pool extends Identifiable
{
    /**
     * @var Collection<int|string, PoolUser>
     */
    protected Collection $users;
    protected bool $halted = false;

    public function __construct(
        protected PoolCollection $collection,
        protected CompetitionConfig $competitionConfig
    ) {
        $this->users = new ArrayCollection();
    }

    public function getCollection(): PoolCollection
    {
        return $this->collection;
    }

    public function getCompetitionConfig(): CompetitionConfig
    {
        return $this->competitionConfig;
    }

    public function getSeason(): Season
    {
        return $this->getSourceCompetition()->getSeason();
    }

    public function getSourceCompetition(): Competition
    {
        return $this->getCompetitionConfig()->getSourceCompetition();
    }

//    public function getSourceCompetitionId(): int
//    {
//        return (int)$this->sourceCompetition->getId();
//    }

    /**
     * @return Collection<int|string, PoolUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getUser(User $user): ?PoolUser
    {
        $filtered = $this->getUsers()->filter(function (PoolUser $poolUser) use ($user): bool {
            return $poolUser->getUser() === $user;
        });
        $firstPoolUser = $filtered->first();
        return $firstPoolUser === false ? null : $firstPoolUser;
    }

    /**
     * @return list<Competition>
     */
    public function getCompetitions(): array
    {
        $leagues = $this->getCollection()->getAssociation()->getLeagues()->toArray();
        $competitions = array_map( function ($league): Competition|null {
            return $league->getCompetition($this->getSeason());
        }, $leagues);
        return array_values(array_filter($competitions, fn (Competition|null $c) => $c !== null));
    }

    public function getCompetition(S11League $s11League): ?Competition
    {
        $league = $this->getCollection()->getLeague($s11League);
        if ($league === null) {
            return null;
        }
        return $league->getCompetition($this->getSeason());
    }

    public function getLeague(Competition $competition): S11League
    {
        foreach (S11League::cases() as $s11League) {
            if ($this->getCompetition($s11League) === $competition) {
                return $s11League;
            }
        }
        throw new \Exception('competition has an unknown league', E_ERROR);
    }

    /**
     * @param Competition $competition
     * @return list<Competitor>
     */
    public function getCompetitors(Competition $competition): array
    {
        $competitors = [];
        foreach ($this->getUsers() as $poolUser) {
            $competitor = $poolUser->getCompetitor($competition);
            if ($competitor === null) {
                continue;
            }
            $competitors[] = $competitor;
        }
        return $competitors;
    }

    public function getName(): string
    {
        return $this->getCollection()->getName();
    }

//    /**
//     * @return ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
//     */
//    public function getScores(): ArrayCollection|PersistentCollection
//    {
//        return $this->scores;
//    }

    public function getUnhaltedPrevious(): Pool|null
    {
        $previous = null;
        $seasonStartDateTime = $this->getSeason()->getStartDateTime();
        foreach ($this->getSiblings() as $sibling) {
            $siblingStart = $sibling->getSeason()->getStartDateTime();
            if ($siblingStart < $seasonStartDateTime && !$sibling->isHalted()
                && ($previous === null || $siblingStart > $previous->getSeason()->getStartDateTime())) {
                $previous = $sibling;
            }
        }
        return $previous;
    }

    /**
     * @return Collection<int|string, Pool>
     */
    public function getSiblings(): Collection
    {
        return $this->getCollection()->getPools();
    }

    public function getPoints(): Points
    {
        return $this->competitionConfig->getPoints();
    }

    public function getAssemblePeriod(): AssemblePeriod
    {
        return $this->competitionConfig->getAssemblePeriod();
    }

    public function getTransferPeriod(): TransferPeriod
    {
        return $this->competitionConfig->getTransferPeriod();
    }

    public function isHalted(): bool
    {
        return $this->halted;
    }

    public function halt(): void
    {
        $this->halted = true;
    }
}
