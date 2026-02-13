<?php

declare(strict_types=1);

namespace SuperElf\Sport;

use Sports\Sport;
use Sports\Repositories\SportRepository;

final class Administrator
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
