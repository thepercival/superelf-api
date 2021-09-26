<?php
declare(strict_types=1);

namespace SuperElf\Sport;

use SuperElf\CompetitionType;
use Sports\Sport;
use SportsHelpers\GameMode;
use SuperElf\Pool;
use SuperElf\PoolCollection;
use Sports\Sport\Repository as SportRepository;

class Administrator
{
    public const SportName = 'superelf';

    public function __construct(protected SportRepository $sportRepos) {
    }

    /**
     * @param Pool $ppol
     * @return array<int, Sport>
     */
    public function getCompetitionTypes(Pool $ppol): array {
        $competitionTypes = [
            CompetitionType::COMPETITION => $this->getSport(CompetitionType::COMPETITION),
            CompetitionType::CUP => $this->getSport(CompetitionType::CUP)
        ];
        if( $ppol->getPrevious() !== null ) {
            $competitionTypes[CompetitionType::SUPERCUP] = $this->getSport(CompetitionType::SUPERCUP);
        }
        return $competitionTypes;
    }

    protected function getSport(int $leagueNumber): Sport {
        $sportName = $this->getName($leagueNumber);
        $sport = $this->sportRepos->findOneBy( ["name" => $sportName] );
        if( $sport === null ) {
            $gameMode = $this->getGameMode($leagueNumber);
            $nrOfSidePlaces = $this->getNrOfSidePlaces($leagueNumber);
            $sport = new Sport( self::SportName, false, $gameMode, $nrOfSidePlaces );
            $this->sportRepos->save( $sport );
        }
        return $sport;
    }

    protected function getGameMode(int $leagueNumber): int {
        if( $leagueNumber === CompetitionType::COMPETITION) {
            return GameMode::ALL_IN_ONE_GAME;
        }
        return GameMode::AGAINST;
    }

    protected function getNrOfSidePlaces(int $leagueNumber): int {
        if( $leagueNumber === CompetitionType::COMPETITION) {
            return 1;
        }
        return 0;
    }

    protected function getName(int $leagueNumber): string {
        if( $leagueNumber === CompetitionType::COMPETITION) {
            return self::SportName . '-' . 'allinonegame';
        }
        return self::SportName . '-' . 'against';
    }
}
