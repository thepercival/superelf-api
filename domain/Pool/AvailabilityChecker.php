<?php

declare(strict_types=1);

namespace SuperElf\Pool;

use Sports\Association\Repository as AssociationRepository;
use Sports\Season;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\User;

class AvailabilityChecker
{
    protected PoolCollectionRepository $poolCollectionRepos;
    protected AssociationRepository $associationRepos;

    public function __construct(
        AssociationRepository $associationRepos,
        PoolCollectionRepository $poolCollectionRepos
    )
    {
        $this->associationRepos = $associationRepos;
        $this->poolCollectionRepos = $poolCollectionRepos;
    }

    public function check(Season $season, string $name, User $user): void
    {
        $association = $this->associationRepos->findOneBy(["name" => $name ]);
        if ($association === null) {
            return;
        }
        $poolCollection = $this->poolCollectionRepos->findOneBy(["association" => $association ]);
        if ($poolCollection === null) {
            return;
        }
        $latestPool = $poolCollection->getLatestPool();
        if ($latestPool === null) {
            return;
        }
        if ($latestPool->getSeason() === $season) {
            throw new \Exception("de pool met naam ".$name." en seizoen ".$season->getName()." bestaat al", E_ERROR);
        }
        $poolUser = $latestPool->getUser($user);
        if ($poolUser === null || !$poolUser->getAdmin()) {
            throw new \Exception("alleen de beheerder van het vorige seizoen van pool ".$name." kan de nieuwe pool aanmaken", E_ERROR);
        }
    }
}
