<?php
declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use SuperElf\Substitute\Appearance;
use SuperElf\Substitute\Appearance\Repository as AppearanceRepository;
use SuperElf\GameRound;
use SuperElf\Formation\Line\Repository as FormationLineRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Formation\Line as FormationLine;

class Calculator
{
    public function __construct(
        protected FormationLineRepository $formationLineRepos,
        protected GameRoundRepository $gameRoundRepos,
        protected PlayerRepository $playerRepos,
        protected AppearanceRepository $appearanceRepos,
        protected ViewPeriodRepository $viewPeriodRepos
    ) {
    }

    public function calculate(
        S11Player $s11Player,
        int $lineNumber,
        GameRound $gameRound,
        array $seasonScoreUnits
    ): void
    {
        $lines = $this->formationLineRepos->findByExt($lineNumber, $viewperiodperson);
        foreach ($lines as $line) {
            $substitute = $line->getSubstitute();
            if ($substitute === null) {
                continue;
            }
            $removed = $this->removeParticipation($substitute, $gameRound);
            $needSubstitute = false; // @TODO CDK $line->needSubstitute( $gameRound );
            if (!$needSubstitute) {
                $this->addParticipation($substitute, $gameRound);
            }
            if ($removed || $needSubstitute) {
                // $substitute->calculatePoints( $seasonScoreUnits );
                // @TODO CDK
                // $this->viewPeriodPersonRepos->save($substitute);
            }
        }
    }

    protected function removeAppearance(FormationLine $formationLine, GameRound $gameRound): bool
    {
        $appearance = $formationLine->getAppareance($gameRound);
        if ($appearance === null) {
            return false;
        }
        $formationLine->getSubstituteAppearances()->removeElement($appearance);
        $this->appearanceRepos->remove($appearance);
        return true;
    }

    protected function addAppearance(FormationLine $line, GameRound $gameRound): void
    {
        $appearance = new Appearance($line, $gameRound);
        $this->appearanceRepos->save($appearance);
    }
}
