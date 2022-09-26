<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Poule;
use SportsHelpers\Identifiable;
use SuperElf\Pool\User as PoolUser;

class ChatMessage extends Identifiable
{
    protected \DateTimeImmutable $dateTime;

    public function __construct(protected Poule $poule, protected PoolUser $poolUser, protected string $message)
    {
        $this->dateTime = new \DateTimeImmutable();
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getPoolUser(): PoolUser
    {
        return $this->poolUser;
    }

    public function getUser(): User
    {
        return $this->poolUser->getUser();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->dateTime;
    }
}
