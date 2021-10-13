<?php
declare(strict_types=1);

namespace SuperElf\Sport;

use SuperElf\CompetitionType;
use Sports\Sport;
use SportsHelpers\GameMode;
use SuperElf\Pool;
use SportsHelpers\Sport\PersistVariant as SportPersistVariant;
use Sports\Sport\Repository as SportRepository;

class Administrator
{
    public const SportName = 'superelf';

    public function __construct(protected SportRepository $sportRepos) {
    }

    /**
     * @param Pool $pool
     * @return list<int>
     */
    public function getCompetitionTypes(Pool $pool): array {
        $competitionTypes = [
            CompetitionType::COMPETITION, CompetitionType::CUP
        ];
        if( $pool->getPrevious() !== null ) {
            $competitionTypes[] = CompetitionType::SUPERCUP;
        }
        return $competitionTypes;
    }

    public function getSport(): Sport {
        $sport = $this->sportRepos->findOneBy( ["name" => self::SportName] );
        if( $sport === null ) {
            throw new \Exception('the sport "' . self::SportName . '"could not be found ', E_ERROR);
        }
        return $sport;
    }
}
