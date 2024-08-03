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
    protected bool $present = false;
    protected string|null $publicInfo = null;
    protected string|null $privateInfo = null;

    public function __construct(
        protected PoolUser $poolUser,
        protected Competition $competition,
        StartLocation $startLoc
    ) {
        parent::__construct($startLoc->getCategoryNr(), $startLoc->getPouleNr(), $startLoc->getPlaceNr());
        if ($poolUser->getCompetitor($competition) === null) {
            $poolUser->getCompetitors()->add($this);
        }
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

    public function getPresent(): bool
    {
        return $this->present;
    }

    public function setPresent(bool $present): void
    {
        $this->present = $present;
    }

    public function getPublicInfo(): string|null
    {
        return $this->publicInfo;
    }

    public function setPublicInfo(string $publicInfo = null): void
    {
        if ($publicInfo !== null && strlen($publicInfo) === 0) {
            $publicInfo = null;
        }
        if ($publicInfo !== null && strlen($publicInfo) > self::MAX_LENGTH_INGO) {
            throw new InvalidArgumentException(
                'de extra-publicInfo mag maximaal ' . self::MAX_LENGTH_INGO . ' karakters bevatten', E_ERROR
            );
        }
        $this->publicInfo = $publicInfo;
    }

    public function getPrivateInfo(): string|null
    {
        return $this->privateInfo;
    }

    public function setPrivateInfo(string $privateInfo = null): void
    {
        if ($privateInfo !== null && strlen($privateInfo) === 0) {
            $privateInfo = null;
        }
        if ($privateInfo !== null && strlen($privateInfo) > self::MAX_LENGTH_INGO) {
            throw new InvalidArgumentException(
                'de extra-privateInfo mag maximaal ' . self::MAX_LENGTH_INGO . ' karakters bevatten', E_ERROR
            );
        }
        $this->privateInfo = $privateInfo;
    }
}
