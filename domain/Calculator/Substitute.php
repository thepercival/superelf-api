<?php

declare(strict_types=1);

namespace SuperElf\Calculator;

use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Participation;
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
    protected FormationLineRepository $formationLineRepos;
    protected GameRoundRepository $gameRoundRepos;
    protected ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos;
    protected ViewPeriodPersonRepository $viewPeriodPersonRepos;
    protected ViewPeriodRepository $viewPeriodRepos;

    public function __construct(
        FormationLineRepository $formationLineRepos,
        GameRoundRepository $gameRoundRepos,
        ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos,
        ViewPeriodPersonRepository $viewPeriodPersonRepos,
        ViewPeriodRepository $viewPeriodRepos
    ) {
        $this->formationLineRepos = $formationLineRepos;
        $this->gameRoundRepos = $gameRoundRepos;
        $this->gameRoundScoreRepos = $gameRoundScoreRepos;
        $this->viewPeriodPersonRepos = $viewPeriodPersonRepos;
        $this->viewPeriodRepos = $viewPeriodRepos;
    }

    /**
     * @param ViewPeriodPerson $viewperiodperson
     * @param int $lineNumber
     * @param GameRound $gameRound
     * @param array|SeasonScoreUnit[] $seasonScoreUnits
     */
    public function calculate( ViewPeriodPerson $viewperiodperson, int $lineNumber, GameRound $gameRound, array $seasonScoreUnits )
    {
        $lines = $this->formationLineRepos->findByExt( $lineNumber, $viewperiodperson );
        foreach( $lines as $line ) {
            $removed = $this->removeParticipation( $line->getSubstitute(), $gameRound);
            $needSubstitute = $line->needSubstitute( $gameRound );
            if( $needSubstitute ) {
                $this->addParticipation( $line->getSubstitute(), $gameRound );
            }
            if( $removed || $needSubstitute ) {
                $line->getSubstitute()->calculatePoints( $seasonScoreUnits );
                $this->viewPeriodPersonRepos->save($line->getSubstitute());
            }
        }
     }

     protected function removeParticipation( PoolUserViewPeriodPerson $substiute, GameRound $gameRound): bool {
        $participation = $substiute->getParticipation( $gameRound );
        if( $participation === null ) {
            return false;
        }
        $substiute->getParticipations()->removeElement( $participation );
        $this->formationLineRepos->remove($participation);
        return true;
     }

    protected function addParticipation( PoolUserViewPeriodPerson $substiute, GameRound $gameRound) {

        $participation = new Participation( $substiute, $gameRound );
        $this->formationLineRepos->save($participation);
    }
}
