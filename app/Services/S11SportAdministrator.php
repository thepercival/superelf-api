<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Sports\SportRepository;
use Sports\Sport;

final class S11SportAdministrator
{
    public const SportName = 'superelf';

    public function __construct(protected SportRepository $sportRepos)
    {
    }

    public function getSport(): Sport
    {
        $sport = $this->sportRepos->findOneBy(["name" => self::SportName]);
        if ($sport === null) {
            throw new \Exception('the sport "' . self::SportName . '"could not be found ', E_ERROR);
        }
        return $sport;
    }
}
