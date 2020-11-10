<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use SuperElf\Pool;
use SuperElf\User;
use SuperElf\Role;

class Shell
{
    /**
     * @var int
     */
    private $poolId;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $seasonName;
    /**
     * @var int
     */
    private $roles;

    public function __construct(Pool $pool, User $user = null)
    {
        $this->poolId = $pool->getId();
        $this->name = $pool->getCollection()->getName();
        $this->seasonName = $pool->getSeason()->getName();

        $this->roles = 0;
        if ($user !== null) {
            $poolUser = $pool->getUser( $user );
            if ($poolUser !== null) {
                $this->roles = Role::COMPETITOR;
                if( $poolUser->getAdmin() ) {
                    $this->roles += Role::ADMIN;
                }
            }
        }
    }

    public function getPoolId(): int
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
