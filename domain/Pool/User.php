<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Sports\Competition;
use SportsHelpers\Identifiable;
use SuperElf\Competitor;
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\Substitution;
use SuperElf\Transfer;
use SuperElf\User as BaseUser;
use SuperElf\Pool\User\GameRoundScore as GameRoundScore;

class User extends Identifiable {
    protected bool $admin;
    protected Formation|null $assembleFormation = null;
    protected Formation|null $transferFormation = null;
    /**
     * @var ArrayCollection<int|string, Transfer>|PersistentCollection<int|string, Transfer>
     */
    protected ArrayCollection|PersistentCollection $transfers;
    /**
     * @var ArrayCollection<int|string, Substitution>|PersistentCollection<int|string, Substitution>
     */
    protected ArrayCollection|PersistentCollection $substitutions;
    /**
     * @var ArrayCollection<int|string, Competitor>|PersistentCollection<int|string, Competitor>
     */
    protected ArrayCollection|PersistentCollection $competitors;
    /**
     * @var ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
     */
    protected ArrayCollection|PersistentCollection $scores;

    public function __construct(protected Pool $pool, protected BaseUser $user )
    {
        $this->admin = false;
        $this->competitors = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->substitutions = new ArrayCollection();
        $this->scores = new ArrayCollection();
    }

    public function getPool(): Pool {
        return $this->pool;
    }

    public function getUser(): BaseUser {
        return $this->user;
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void
    {
        $this->admin = $admin;
    }

    /**
     * @return ArrayCollection<int|string, Competitor>|PersistentCollection<int|string, Competitor>
     */
    public function getCompetitors(): ArrayCollection|PersistentCollection
    {
        return $this->competitors;
    }

    public function getCompetitor(Competition $competition): Competitor|null
    {
        $filtered = $this->competitors->filter( function( Competitor $competitor ) use ($competition): bool {
            return $competitor->getCompetition() === $competition;
        });
        $firstCompetitor = $filtered->first();
        return $firstCompetitor === false ? null : $firstCompetitor;
    }

    /**
     * @return Collection<string|int, Transfer>
     */
    public function getTransfers( bool $outHasTeam = null ): Collection
    {
        if( $outHasTeam === null ) {
            return $this->transfers;
        }
        return $this->transfers->filter( function( Transfer $transfer ) use ($outHasTeam): bool {
            return $transfer->outHasTeam() === $outHasTeam;
        });
    }

    /**
     * @return ArrayCollection<int|string, Substitution>|PersistentCollection<int|string, Substitution>
     */
    public function getSubstitutions(): ArrayCollection|PersistentCollection
    {
        return $this->substitutions;
    }

    public function getAssembleFormation(): Formation|null {
        return $this->assembleFormation;
    }

    public function setAssembleFormation( Formation $formation = null ): void {
        $this->assembleFormation = $formation;
    }

    public function getTransferFormation(): ?Formation {
        return $this->transferFormation;
    }

    public function setTransferFormation( Formation $formation ): void {
        $this->transferFormation = $formation;
    }

    public function getNrOfAssembled(): int {
        $formation = $this->getAssembleFormation();
        return $formation !== null ? $formation->getNrOfPersons() : 0;
    }

    public function getNrOfTransferedWithTeam(): int {
        return $this->getTransfers( true )->count();
    }

    /**
     * @return ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
     */
    public function getScores(): ArrayCollection|PersistentCollection
    {
        return $this->scores;
    }
}