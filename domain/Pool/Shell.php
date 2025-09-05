<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\Pool;
use SuperElf\Role;
use SuperElf\User;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Shell
{
    private int|string $poolId;
    private string $name;
    private string $seasonName;
    private int $roles;
    private int|null $nrOfUsers = null;

    public function __construct(Pool $pool, User $user = null, bool $nrOfUsers = false)
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
        if( $nrOfUsers ) {
            $this->nrOfUsers = count($pool->getUsers());
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

    public function getNrOfUsers(): int
    {
        if( $this->nrOfUsers === null ) {
            throw new \Exception('should be initialized with nrOfUsers = true', E_ERROR);
        }
        return $this->nrOfUsers;
    }
}
