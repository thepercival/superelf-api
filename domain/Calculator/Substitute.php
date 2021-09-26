<?php
declare(strict_types=1);

namespace SuperElf\Calculator;

use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;
use SuperElf\Pool\User\ViewPeriodPerson\Participation\Repository as ParticipationRepository;
use SuperElf\GameRound;
use SuperElf\Formation\Line\Repository as FormationLineRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View\Person\GameRoundScore\Repository as ViewPeriodPersonGameRoundScoreRepository;
use SuperElf\Period\View\Person\Repository as ViewPeriodPersonRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Period\View\Person as ViewPeriodPerson;
use SuperElf\Season\ScoreUnit as SeasonScoreUnit;

class Substitute
{
    public function __construct(
        protected FormationLineRepository $formationLineRepos,
        protected GameRoundRepository $gameRoundRepos,
        protected ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos,
        protected ViewPeriodPersonRepository $viewPeriodPersonRepos,
        protected ParticipationRepository $participationRepos,
        protected ViewPeriodRepository $viewPeriodRepos
    ) {
    }

    /**
     * @param ViewPeriodPerson $viewperiodperson
     * @param int $lineNumber
     * @param GameRound $gameRound
     * @param list<SeasonScoreUnit> $seasonScoreUnits
     */
    public function calculate(
        ViewPeriodPerson $viewperiodperson,
        int $lineNumber,
        GameRound $gameRound, array $seasonScoreUnits ): void
    {
        $lines = $this->formationLineRepos->findByExt( $lineNumber, $viewperiodperson );
        foreach( $lines as $line ) {
            $substitute = $line->getSubstitute();
            if( $substitute === null ) {
                continue;
            }
            $removed = $this->removeParticipation( $substitute, $gameRound);
            $needSubstitute = $line->needSubstitute( $gameRound );
            if( !$needSubstitute ) {
                $this->addParticipation( $substitute, $gameRound );
            }
            if( $removed || $needSubstitute ) {
                $substitute->calculatePoints( $seasonScoreUnits );
                $this->viewPeriodPersonRepos->save($substitute);
            }
        }
     }

     protected function removeParticipation( PoolUserViewPeriodPerson $substiute, GameRound $gameRound): bool {
        $participation = $substiute->getParticipation( $gameRound );
        if( $participation === null ) {
            return false;
        }
        $substiute->getParticipations()->removeElement( $participation );
        $this->participationRepos->remove($participation);
        return true;
     }

    protected function addParticipation( PoolUserViewPeriodPerson $substiute, GameRound $gameRound): void {

        $participation = new Participation( $substiute, $gameRound );
        $this->participationRepos->save($participation);
    }
}
