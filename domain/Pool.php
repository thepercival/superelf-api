<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Season;
use Sports\Competition;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Pool\GameRoundScore;

class Pool
{
    /**
     * @var string | int
     */
    protected $id;
    protected PoolCollection $collection;
    protected Competition $sourceCompetition;
    protected ViewPeriod $createAndJoinPeriod;
    protected AssemblePeriod $assemblePeriod;
    protected TransferPeriod $transferPeriod;
    /**
     * @var ArrayCollection|PoolUser[]
     */
    protected $users;
    /**
     * @var ArrayCollection|GameRoundScore[]
     */
    protected $scores;

    public function __construct( PoolCollection $collection, Competition $sourceCompetition,
        ViewPeriod $createAndJoinPeriod, AssemblePeriod $assemblePeriod, TransferPeriod $transferPeriod
    )
    {
        $this->collection = $collection;
        $this->sourceCompetition = $sourceCompetition;
        $this->createAndJoinPeriod = $createAndJoinPeriod;
        $this->assemblePeriod = $assemblePeriod;
        $this->transferPeriod = $transferPeriod;
        $this->users = new ArrayCollection();
        $this->scores = new ArrayCollection();
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

    public function getCreateAndJoinPeriod(): ViewPeriod
    {
        return $this->createAndJoinPeriod;
    }

    public function getCollection(): PoolCollection {
        return $this->collection;
    }

    public function getSeason(): Season {
        return $this->getSourceCompetition()->getSeason();
    }

    public function getSourceCompetition(): Competition {
        return $this->sourceCompetition;
    }

    public function getSourceCompetitionId(): int {
        return $this->sourceCompetition->getId();
    }

    public function getAssemblePeriod(): AssemblePeriod {
        return $this->assemblePeriod;
    }

    public function getTransferPeriod(): TransferPeriod {
        return $this->transferPeriod;
    }

    public function isInAssembleOrTransferPeriod(): bool {
    return $this->getAssemblePeriod()->contains() || $this->getTransferPeriod()->contains();
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
     * @return ArrayCollection|GameRoundScore[]
     */
    public function getScores()
    {
        return $this->scores;
    }
}
