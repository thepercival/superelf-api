<?php

namespace SuperElf;

use Sports\Person;
use SportsHelpers\Identifiable;
use SuperElf\Pool\User as PoolUser;

class Substitution extends Identifiable {

    public function __construct(protected PoolUser $poolUser, protected Person $out, protected Person $in )
    {
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
}