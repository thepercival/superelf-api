<?php

declare(strict_types=1);

namespace SuperElf;

use SuperElf\Pool\User as PoolUser;
use Sports\Competition;
use Sports\Competitor\Base;
use Sports\Competitor as SportsCompetitor;

class Competitor implements SportsCompetitor
{
    /**
     * @var int|string
     */
    protected $id;
    protected PoolUser $poolUser;
    protected Competition $competition;
    protected bool $admin;

    use Base;

    public function __construct( PoolUser $poolUser, Competition $competition, int $pouleNr, int $placeNr)
    {
        $this->setPoolUser($poolUser);
        $this->competition = $competition;
        $this->setPouleNr( $pouleNr );
        $this->setPlaceNr( $placeNr );
        $this->admin = false;
    }

    public function getPool(): Pool
    {
        return $this->poolUser->getPool();
    }

    public function getPoolUser(): PoolUser
    {
        return $this->poolUser;
    }

    public function setPoolUser(PoolUser $poolUser)
    {
        if (!$poolUser->getCompetitors()->contains($this)) {
            $poolUser->getCompetitors()->add($this) ;
        }
        $this->poolUser = $poolUser;
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

    public function getCompetitionId(): int {
        return $this->getCompetition()->getId();
    }
}
