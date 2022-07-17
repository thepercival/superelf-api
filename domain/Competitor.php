<?php

declare(strict_types=1);

namespace SuperElf;

use InvalidArgumentException;
use Sports\Competition;
use Sports\Competitor as SportsCompetitor;
use Sports\Competitor\StartLocation;
use SuperElf\Pool\User as PoolUser;

class Competitor extends StartLocation implements SportsCompetitor
{
    public const MAX_LENGTH_INGO = 200;

    protected int|string|null $id = null;
    protected bool $registered = false;
    protected string|null $info = null;

    public function __construct(
        protected PoolUser $poolUser,
        protected Competition $competition,
        StartLocation $startLoc
    ) {
        parent::__construct($startLoc->getCategoryNr(), $startLoc->getPouleNr(), $startLoc->getPlaceNr());
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

    public function getRegistered(): bool
    {
        return $this->registered;
    }

    public function setRegistered(bool $registered): void
    {
        $this->registered = $registered;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info = null): void
    {
        if ($info !== null && strlen($info) === 0) {
            $info = null;
        }
        if ($info !== null && strlen($info) > self::MAX_LENGTH_INGO) {
            throw new InvalidArgumentException(
                'de extra-info mag maximaal ' . self::MAX_LENGTH_INGO . ' karakters bevatten', E_ERROR
            );
        }
        $this->info = $info;
    }
}
