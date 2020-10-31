<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Competitor\Base;
use Sports\Competitor as SportsCompetitor;

class Competitor implements SportsCompetitor
{
    /**
     * @var int|string
     */
    protected $id;
    protected Pool $pool;
    protected Competition $competition;
    protected User $user;
    protected bool $admin;

    use Base;

    public function __construct(
        Pool $pool, Competition $competition, User $user, int $pouleNr, int $placeNr)
    {
        $this->setPool($pool);
        $this->competition = $competition;
        $this->user = $user;
        $this->setPouleNr( $pouleNr );
        $this->setPlaceNr( $placeNr );
        $this->admin = false;
    }

    public function getPool(): Pool
    {
        return $this->pool;
    }

    public function setPool(Pool $pool)
    {
        if (!$pool->getCompetitors()->contains($this)) {
            $pool->getCompetitors()->add($this) ;
        }
        $this->pool = $pool;
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
}
