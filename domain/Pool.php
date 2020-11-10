<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Sport\Formation;
use Sports\Season;
use Sports\Competition;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\Period as PoolPeriod;
use SuperElf\Pool\ScoreUnit as PoolScoreUnit;

class Pool
{
    /**
     * @var string | int
     */
    protected $id;
    protected PoolCollection $collection;
    protected Season $season;
    protected Competition $sourceCompetition;
    /**
     * @var ArrayCollection|PoolScoreUnit[]
     */
    protected $scoreUnits;
    /**
     * @var ArrayCollection|PoolPeriod[]
     */
    protected $periods;
    /**
     * @var ArrayCollection|PoolUser[]
     */
    protected $users;

    public function __construct( PoolCollection $collection, Season $season, Competition $sourceCompetition )
    {
        $this->collection = $collection;
        $this->season = $season;
        $this->sourceCompetition = $sourceCompetition;
        $this->periods = new ArrayCollection();
        $this->scoreUnits = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    public function getCollection(): PoolCollection {
        return $this->collection;
    }

    public function getSeason(): Season {
        return $this->season;
    }

    public function getSourceCompetition(): Competition {
        return $this->sourceCompetition;
    }

    public function getSourceCompetitionId(): int {
        return $this->sourceCompetition->getId();
    }

    /**
     * @return ArrayCollection|PoolPeriod[]
     */
    public function getPeriods() {
        return $this->periods;
    }

    /**
     * @return ArrayCollection|PoolUser[]
     */
    public function getUsers() {
        return $this->users;
    }

    public function getUser( User $user ): ?PoolUser {
        $filtered = $this->getUsers()->filter( function( PoolUser $poolUser ) use ($user) : bool {
            return $poolUser->getUser() === $user;
        });
        return $filtered->count() === 0 ? null : $filtered->first();
    }

    /**
     * @return array|Competition[]
     */
    public function getCompetitions() {
        $leagues = $this->getCollection()->getAssociation()->getLeagues();
        $competitions = $leagues->map( function ( $league ): ?Competition {
            return $league->getCompetition( $this->getSeason() );
        } )->toArray();
        return array_filter( $competitions );
    }

    public function getCompetition( int $leagueNr = null ): ?Competition {
        $league = $this->getCollection()->getLeague( $leagueNr );
        if ( $league === null ) {
            return null;
        }
        return $league->getCompetition( $this->getSeason() );
    }

    public function getName(): string
    {
        return $this->getCollection()->getAssociation()->getName();
    }

    /**
     * @return ArrayCollection|PoolScoreUnit[]
     */
    public function getScoreUnits() {
        return $this->scoreUnits;
    }

    /**
     * @return ArrayCollection|Formation[]
     */
    public function getFormations() {
        return $this->getCompetition()->getFirstSportConfig()->getSport()->getFormations();
    }

}
