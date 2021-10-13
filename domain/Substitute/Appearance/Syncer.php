<?php
declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use SuperElf\Points;
use SuperElf\Substitute\Appearance;
use SuperElf\Substitute\Appearance\Repository as AppearanceRepository;
use SuperElf\GameRound;
use SuperElf\Formation\Line\Repository as FormationLineRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Formation\Line as FormationLine;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected FormationLineRepository $formationLineRepos,
        protected GameRoundRepository $gameRoundRepos,
        protected PlayerRepository $playerRepos,
        protected AppearanceRepository $appearanceRepos,
        protected ViewPeriodRepository $viewPeriodRepos
    ) {
    }

    public function sync(AgainstGame $game): void {
        $competition = $game->getRound()->getNumber()->getCompetition();
        // viewperiods for season

        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new CompetitorMap($competitors);
//
        $viewPeriod = $this->viewPeriodRepos->findOneByDate($competition, $game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new Exception('the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601), E_ERROR);
        }
        // foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAway) {
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $teamCompetitor = $map->getCompetitor($gamePlace->getPlace());
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            // remove substituteAppearances which should not be
            // add substituteAppearances which should be
            // SQL

            // delete
            // from     substituteAppearances app
            //          join formationLines fl on app.formationLineId
            // where
            //          not exists (
            //          select  count(*)
            //          from    statistics s
            //                  join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
            //          where   s.gameRoundId = **INPUT_GAMEROUND**
            //          and     fl.viewPeriodId = **INPUT_VIEWPERIOD**
            //          and     s.beginMinute > -1
            //          )
            // or
            //          (
            //          select  count(*)
            //          from    statistics s
            //                  join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
            //          where   s.gameRoundId = **INPUT_GAMEROUND**
            //          and     fl.viewPeriodId = **INPUT_VIEWPERIOD**
            //          ) < f.maxNrOfPersons

            // insert into
            // from     substituteAppearances app
            //          join formationLines fl on app.formationLineId
            // where
            //          exists (
            //          select  count(*)
            //          from    statistics s
            //                  join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
            //          where   s.gameRoundId = **INPUT_GAMEROUND**
            //          and     fl.viewPeriodId = **INPUT_VIEWPERIOD**
            //          and     s.beginMinute > -1
            //          )
            // and
            //          (
            //          select  count(*)
            //          from    statistics s
            //                  join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
            //          where   s.gameRoundId = **INPUT_GAMEROUND**
            //          and     fl.viewPeriodId = **INPUT_VIEWPERIOD**
            //          ) === f.maxNrOfPersons

            // $this->syncLine($viewPeriod, $gamePlace, $teamCompetitor->getTeam());
//            $formationLines = $this->formationLineRepos->getLinesToMakeSubstituteAppearance($gameRound, $lineNumber);
//            foreach ($formationLines as $formationLine) {
//                if ($formationLine->getAppearance() === null) {
//                    $this->addAppearance($formationLine, $gameRound);
//                }
//            }
        }
    }
//
//    protected function removeAppearance(FormationLine $formationLine, GameRound $gameRound): bool
//    {
//        $appearance = $formationLine->getAppareance($gameRound);
//        if ($appearance === null) {
//            return false;
//        }
//        $formationLine->getSubstituteAppearances()->removeElement($appearance);
//        $this->appearanceRepos->remove($appearance);
//        return true;
//    }
//
    protected function addAppearance(FormationLine $line, GameRound $gameRound): void
    {
        $appearance = new Appearance($line, $gameRound);
        $this->appearanceRepos->save($appearance);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function logInfo(string $info): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->info($info);
    }
}
