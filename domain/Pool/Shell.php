<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\Pool;
use SuperElf\Role;
use SuperElf\User;

class Shell
{
    private int|string $poolId;
    private string $name;
    private string $seasonName;
    private int $roles;

    public function __construct(Pool $pool, User $user = null)
    {
        $this->poolId = (string)$pool->getId();
        $this->name = $pool->getName();
        $this->seasonName = $pool->getSeason()->getName();

        $this->roles = 0;
        if ($user !== null) {
            $poolUser = $pool->getUser($user);
            if ($poolUser !== null) {
                $this->roles = Role::COMPETITOR;
                if ($poolUser->getAdmin()) {
                    $this->roles += Role::ADMIN;
                }
            }
        }
    }

    public function getPoolId(): int|string
    {
        return $this->poolId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSeasonName(): string
    {
        return $this->seasonName;
    }

    public function getRoles(): int
    {
        return $this->roles;
    }
}
