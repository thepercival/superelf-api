<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use SuperElf\CompetitionConfig;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Achievement as AchievementBase;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class Badge extends AchievementBase
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

    public function getPoolName(): string {
        return $this->pool?->getName() ?? '';
    }

    public function getSeasonShortName(): string {
        $name = $this->competitionConfig->getSeason()->getName();
        if( $name === '2019/2020') {
            return '19/20';
        } else if( $name === '2020/2021') {
            return '20/21';
        } else if ( $name === '2020') {
            return '20';
        }
        while( strpos($name, '20') !== false ) {
            $name = str_replace('20', '', $name);
        }
        return $name;
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

    public function showDescription(): string {
        $cateogry = 'badge("' . $this->category->value . '")';
        $asignedTo = ' assigned to "' . $this->poolUser->getUser()->getName() . '"';
        $scope = ' for ' . $this->getPoolName() . ' ' . $this->getSeasonShortName();
        $asignedAt = ' at "' . $this->createDateTime->format('Y-m-d') . '"';
        return $cateogry . $asignedTo . $scope . $asignedAt;
    }
}

