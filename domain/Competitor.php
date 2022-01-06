<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Competitor as SportsCompetitor;
use Sports\Competitor\Base;
use SuperElf\Pool\User as PoolUser;

class Competitor extends Base implements SportsCompetitor
{
    public function __construct(
        protected PoolUser $poolUser,
        protected Competition $competition,
        int $pouleNr,
        int $placeNr
    )
    {
        parent::__construct($pouleNr,$placeNr);
    }

    public function getPool(): Pool
    {
        return $this->poolUser->getPool();
    }

    public function getPoolUser(): PoolUser
    {
        return $this->poolUser;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function getName(): string
    {
        return $this->getUser()->getName();
    }

    public function getUser(): User
    {
        return $this->poolUser->getUser();
    }

    public function getCompetitionId(): int|string|null
    {
        return $this->getCompetition()->getId();
    }
}
