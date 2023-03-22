<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\CompetitionConfig;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Achievement as AchievementBase;

class Badge extends AchievementBase implements \Stringable
{
    public function __construct(
        protected BadgeCategory $category,
        protected Pool|null $pool,
        protected CompetitionConfig $competitionConfig,
        PoolUser $poolUser,
        \DateTimeImmutable $createDateTime)
    {
        parent::__construct($poolUser,$createDateTime);
    }

    public function getPool(): Pool|null {
        return $this->pool;
    }

    public function getPoolId(): string|int|null {
        return $this->pool?->getId();
    }

    public function getCompetitonConfig(): CompetitionConfig|null {
        return $this->competitionConfig;
    }

    public function getCategory(): BadgeCategory {
        return $this->category;
    }

    public function getCategoryNative(): string
    {
        return $this->category->value;
    }

    public function getScopeDescription(): string {
        $name = $this->competitionConfig->getSeason()->getName();
        while( strpos($name, '20') !== false ) {
            $name = str_replace('20', '', $name);
        }
        return $this->pool === null ? $name : $this->pool->getName() . ' ' . $name;
    }

    public function __toString(): string {
        $cateogry = 'badge("' . $this->category->value . '")';
        $asignedTo = ' assigned to "' . $this->poolUser->getUser()->getName() . '"';
        $scope = ' for ' . $this->getScopeDescription();
        $asignedAt = ' at "' . $this->createDateTime->format('Y-m-d') . '"';
        return $cateogry . $asignedTo . $scope . $asignedAt;
    }
}

