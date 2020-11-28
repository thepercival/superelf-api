<?php

namespace SuperElf\Pool;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Competitor;
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\Substitution;
use SuperElf\Transfer;
use SuperElf\User as BaseUser;
use SuperElf\Pool\User\GameRoundScore as GameRoundScore;

class User {

    protected int $id;
    protected Pool $pool;
    protected BaseUser $user;
    protected bool $admin;
    /**
     * @var Formation | null
     */
    protected $assembleFormation;
    /**
     * @var ArrayCollection | Transfer[]
     */
    protected $transfers;
    /**
     * @var ArrayCollection | Substitution[]
     */
    protected $substitutions;
    /**
     * @var Formation | null
     */
    protected $transferFormation;
    /**
     * @var ArrayCollection|Competitor[]
     */
    protected $competitors;
    /**
     * @var ArrayCollection|GameRoundScore[]
     */
    protected $scores;

    public function __construct(Pool $pool, BaseUser $user )
    {
        $this->setPool( $pool );
        $this->user = $user;
        $this->competitors = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->substitutions = new ArrayCollection();
        $this->scores = new ArrayCollection();
    }

    public function getPool(): Pool {
        return $this->pool;
    }

    public function setPool(Pool $pool)
    {
        if (!$pool->getUsers()->contains($this)) {
            $pool->getUsers()->add($this) ;
        }
        $this->pool = $pool;
    }

    public function getUser(): BaseUser {
        return $this->user;
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin)
    {
        $this->admin = $admin;
    }

    /**
     * @return ArrayCollection|Competitor[]
     */
    public function getCompetitors()
    {
        return $this->competitors;
    }

    /**
     * @return ArrayCollection|Transfer[]
     */
    public function getTransfers( bool $outHasTeam = null )
    {
        if( $outHasTeam === null ) {
            return $this->transfers;
        }
        return $this->transfers->filter( function( Transfer $transfer ) use ($outHasTeam): bool {
            return $transfer->outHasTeam() === $outHasTeam;
        });
    }

    /**
     * @return ArrayCollection|Substitution[]
     */
    public function getSubstitutions()
    {
        return $this->substitutions;
    }

    public function getAssembleFormation(): ?Formation {
        return $this->assembleFormation;
    }

    public function setAssembleFormation( Formation $formation = null ) {
        $this->assembleFormation = $formation;
    }

    public function getTransferFormation(): ?Formation {
        return $this->transferFormation;
    }

    public function setTransferFormation( Formation $formation ) {
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
     * @return ArrayCollection|GameRoundScore[]
     */
    public function getScores()
    {
        return $this->scores;
    }
}