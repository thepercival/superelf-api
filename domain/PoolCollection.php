<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sports\Association;
use Sports\League;

class PoolCollection
{
    /**
     * @var string | int
     */
    protected $id;
    protected string $name;
    protected Association $association;
    /**
     * @var ArrayCollection|Pool[]
     */
    protected $pools;

    protected const MIN_LENGTH_NAME = 3;
    protected const MAX_LENGTH_NAME = 20;

    public const LEAGUE_DEFAULT = 1;
    public const LEAGUE_CUP = 2;
    public const LEAGUE_SUPERCUP = 4;

    public function __construct( Association $association )
    {
        $this->association = $association;
        $this->checkName( $association->getName() );
        $this->pools = new ArrayCollection();
    }

    /**
     * @return string | int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string | int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getAssociation(): Association {
        return $this->association;
    }

    public function getName():string {
        return $this->getAssociation()->getName();
    }

    protected function checkName(string $name ) {
        if (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME) {
            throw new \InvalidArgumentException("de naam moet minimaal ".static::MIN_LENGTH_NAME." karakters bevatten en mag maximaal ".static::MAX_LENGTH_NAME." karakters bevatten", E_ERROR);
        }
    }

    public function getLeague( int $leagueNr = null ): ?League {
        $name = $this->getLeagueName( $leagueNr );
        $filtered = $this->getAssociation()->getLeagues()->filter( function( League $league ) use ($name) : bool {
            return $league->getName() === $name;
        });
        return $filtered->count() === 0 ? null : $filtered->first();
    }

    public function getLeagueName( int $leagueNr = null ): string {
        if( $leagueNr === self::LEAGUE_CUP ) {
            return "beker";
        } else if( $leagueNr === self::LEAGUE_SUPERCUP ) {
            return "supercup";
        }
        return "competitie";
    }

    /**
     * @return ArrayCollection|Pool[]
     */
    public function getPools() {
        return $this->pools;
    }

    /**
     * @return Pool|null
     */
    public function getLatestPool(): ?Pool {
        $pools = $this->getPools()->toArray();
        uasort( $pools, function( Pool $poolA, Pool $poolB ): int {
            return $poolA->getSeason()->getStartDateTime() < $poolB->getSeason()->getStartDateTime() ? -1 : 1;
        });
        return count($pools) === 0 ? null : reset( $pools );
    }
}
