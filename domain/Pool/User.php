<?php

namespace SuperElf\Pool;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Competitor;
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\User as BaseUser;

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
     * @var Formation | null
     */
    protected $transferFormation;
    /**
     * @var ArrayCollection|Competitor[]
     */
    protected $competitors;

    public function __construct(Pool $pool, BaseUser $user )
    {
        $this->setPool( $pool );
        $this->user = $user;
        $this->competitors = new ArrayCollection();
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

    public function getAssembleFormation(): ?Formation {
        return $this->assembleFormation;
    }

    public function setAssembleFormation( Formation $formation ) {
        $this->assembleFormation = $formation;
    }

    public function getTransferFormation(): ?Formation {
        return $this->transferFormation;
    }

    public function setTransferFormation( Formation $formation ) {
        $this->transferFormation = $formation;
    }
}