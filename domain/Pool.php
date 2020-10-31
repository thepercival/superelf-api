<?php

declare(strict_types=1);

namespace SuperElf;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Season;
use Sports\Competition;
use SuperElf\Competitor as PoolCompetitor;

class Pool
{
    /**
     * @var string | int
     */
    protected $id;
    protected PoolCollection $collection;
    protected Season $season;
    /**
     * @var ArrayCollection|PoolCompetitor[]
     */
    protected $competitors;
    /**
     * @var ArrayCollection|Competition[]
     */
    protected $competitions;

    public function __construct( PoolCollection $collection, Season $season )
    {
        $this->collection = $collection;
        $this->season = $season;
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

    /**
     * @return ArrayCollection|PoolCompetitor[]
     */
    public function getCompetitors() {
        return $this->competitors;
    }

    public function getCompetitor( User $user ): ?PoolCompetitor {
        return $this->getCompetitors()->filter( function( PoolCompetitor $competitor ) use ($user) : bool {
            return $competitor->getUser() === $user;
        })->first();
    }

    /**
     * @return ArrayCollection|Competition[]
     */
    public function getCompetitions() {
        return $this->competitions;
    }

    public function getName()
    {
        $this->getCollection()->getName();
    }
}
