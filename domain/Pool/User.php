<?php

namespace SuperElf\Pool;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Competitor;
use SuperElf\Pool;
use SuperElf\User as BaseUser;

class User {

    protected int $id;
    protected Pool $pool;
    protected BaseUser $user;
    protected bool $admin;
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
}