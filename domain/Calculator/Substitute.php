<?php

declare(strict_types=1);

namespace SuperElf\Calculator;

use Sports\Team;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View\Person\GameRoundScore\Repository as ViewPeriodPersonGameRoundScoreRepository;
use SuperElf\Period\View\Person\Repository as ViewPeriodPersonRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Period\View\Person as ViewPeriodPerson;

class Substitute
{
    protected GameRoundRepository $gameRoundRepos;
    protected ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos;
    protected ViewPeriodPersonRepository $viewPeriodPersonRepos;
    protected ViewPeriodRepository $viewPeriodRepos;

    public function __construct(
        GameRoundRepository $gameRoundRepos,
        ViewPeriodPersonGameRoundScoreRepository $gameRoundScoreRepos,
        ViewPeriodPersonRepository $viewPeriodPersonRepos,
        ViewPeriodRepository $viewPeriodRepos
    ) {
        $this->gameRoundRepos = $gameRoundRepos;
        $this->gameRoundScoreRepos = $gameRoundScoreRepos;
        $this->viewPeriodPersonRepos = $viewPeriodPersonRepos;
        $this->viewPeriodRepos = $viewPeriodRepos;
    }

    public function calculate( ViewPeriodPerson $viewperiodperson, int $line, GameRound $gameRound )
    {
        // haal alle linies op waarbij iemand een de viewperiodperson in de basis heeft


//        $competition = $viewPeriodPerson->getViewPeriod()->getSourceCompetition();
//        foreach ($pools as $pool) {
//        }
//        return BaseViewPeriodPerson
     }
}
