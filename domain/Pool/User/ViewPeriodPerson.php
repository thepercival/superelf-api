<?php

declare(strict_types=1);

namespace SuperElf\Pool\User;

use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\Period\View\Person as BaseViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;
use SuperElf\Pool\User as PoolUser;

class ViewPeriodPerson {
    protected int $id;
    protected PoolUser $poolUser;
    protected BaseViewPeriodPerson $viewPeriodPerson;
    /**
     * @var array | int[]
     */
    protected array $totals = [];
    /**
     * @var ArrayCollection | Participation[]
     */
    protected $participations;

    public function __construct( PoolUser $poolUser, BaseViewPeriodPerson $viewPeriodPerson )
    {
        $this->poolUser = $poolUser;
        $this->viewPeriodPerson = $viewPeriodPerson;
        $this->participations = new ArrayCollection();
    }

    public function getPoolUser(): PoolUser {
        return $this->poolUser;
    }

    public function getViewPeriodPerson(): BaseViewPeriodPerson {
        return $this->viewPeriodPerson;
    }

    /**
     * @return ArrayCollection | Participation[]
     */
    public function getParticipations() {
        return $this->participations;
    }

    /**
     * @return array|int[]
     */
    public function getTotals(): array {
        return $this->totals;
    }

    /**
     * @param array|int[] $totals
     */
    public function setTotals(array $totals ) {
        $this->totals = $totals;
    }
}