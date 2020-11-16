<?php

namespace SuperElf;

use Sports\Person;
use SuperElf\Pool\User as PoolUser;

class Transfer {

    protected int $id;
    protected PoolUser $poolUser;
    protected Person $out;
    protected Person $in;
    protected bool $outHasTeam = true;

    public function __construct(PoolUser $poolUser, Person $out, Person $in )
    {
        $this->setPoolUser($poolUser);
        $this->in = $in;
        $this->out = $out;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function setPoolUser(PoolUser $poolUser)
    {
        if (!$poolUser->getTransfers()->contains($this)) {
            $poolUser->getTransfers()->add($this) ;
        }
        $this->poolUser = $poolUser;
    }

    public function getPoolUser(): Pooluser {
        return $this->poolUser;
    }

    public function getOut(): Person {
        return $this->out;
    }

    public function getIn(): Person {
        return $this->in;
    }

    public function outHasTeam(): bool {
        return $this->outHasTeam;
    }
}