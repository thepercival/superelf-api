<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Competition;
use Sports\Season;
use SportsHelpers\Identifiable;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Pool\User as PoolUser;

class Pool extends Identifiable
{
    /**
     * @var Collection<int|string, PoolUser>
     */
    protected Collection $users;
//    /**
//     * @var Collection<int|string, GameRoundScore>
//     */
//    protected $scores;

    public function __construct(
        protected PoolCollection $collection,
        protected Competition $sourceCompetition,
        protected Points $points,
        protected ViewPeriod $createAndJoinPeriod,
        protected AssemblePeriod $assemblePeriod,
        protected TransferPeriod $transferPeriod
    ) {
        $this->users = new ArrayCollection();
//        $this->scores = new ArrayCollection();
    }

    public function getCreateAndJoinPeriod(): ViewPeriod
    {
        return $this->createAndJoinPeriod;
    }

    public function getCollection(): PoolCollection
    {
        return $this->collection;
    }

    public function getSeason(): Season
    {
        return $this->getSourceCompetition()->getSeason();
    }

    public function getSourceCompetition(): Competition
    {
        return $this->sourceCompetition;
    }

    public function getSourceCompetitionId(): int
    {
        return (int)$this->sourceCompetition->getId();
    }

    public function getPoints(): Points
    {
        return $this->points;
    }

    public function getAssemblePeriod(): AssemblePeriod
    {
        return $this->assemblePeriod;
    }

    public function getTransferPeriod(): TransferPeriod
    {
        return $this->transferPeriod;
    }

    public function isInAssembleOrTransferPeriod(): bool
    {
        return $this->getAssemblePeriod()->contains() || $this->getTransferPeriod()->contains();
    }

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
        $leagues = $this->getCollection()->getAssociation()->getLeagues();
        $competitions = $leagues->map(function ($league): ?Competition {
            return $league->getCompetition($this->getSeason());
        })->toArray();
        return array_values(array_filter($competitions, fn (Competition|null $c) => $c !== null));
    }

    public function getCompetition(int $leagueNr): ?Competition
    {
        $league = $this->getCollection()->getLeague($leagueNr);
        if ($league === null) {
            return null;
        }
        return $league->getCompetition($this->getSeason());
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

    public function getPrevious(): Pool|null
    {
        $previousEndDateTime = $this->getSeason()->getStartDateTime();
        $previousStartDateTime = $previousEndDateTime->modify('-2 days');
        $endPeriod = new \League\Period\Period($previousStartDateTime, $previousEndDateTime);

        foreach ($this->getSiblings() as $sibling) {
            if ($endPeriod->contains($sibling->getSeason()->getEndDateTime())) {
                return $sibling;
            }
        }
        return null;
    }

    /**
     * @return Collection<int|string, Pool>
     */
    public function getSiblings(): Collection
    {
        return $this->getCollection()->getPools();
    }
}
