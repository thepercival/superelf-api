<?php

declare(strict_types=1);

namespace SuperElf\Season\ScoreUnit;

use Selective\Config\Configuration;
use Sports\Season;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;
use SuperElf\ScoreUnit as BaseScoreUnit;
use SuperElf\Season\ScoreUnit\Repository as ScoreUnitRepository;

class Creator
{
    protected ScoreUnitRepository $scoreUnitRepos;
    protected Configuration $config;

    public function __construct(
        ScoreUnitRepository $scoreUnitRepos,
        Configuration $config) {
        $this->scoreUnitRepos = $scoreUnitRepos;
        $this->config = $config;
    }

    /**
     * @param Season $season
     * @return array| SeasonScoreUnit[]
     * @throws \Exception
     */
    public function create( Season $season ): array {
        $scoreUnits = $this->scoreUnitRepos->findBy( ["season" => $season ] );
        if( count($scoreUnits) > 0 ) {
            return $scoreUnits;
        }
        foreach( $this->config->getArray("scoreunits" ) as $number => $points ) {
            $scoreUnit = new SeasonScoreUnit($season, new BaseScoreUnit($number ), (int) $points );
            $scoreUnits[] = $this->scoreUnitRepos->save( $scoreUnit );
        }
        return $scoreUnits;
    }
}
