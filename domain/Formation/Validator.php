<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Selective\Config\Configuration;
use Sports\Formation as SportsFormation;
use Sports\Formation\Line as SportsFormationLine;
use Sports\Sport\FootballLine;
use Sports\Team;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Formation\Place\Removal as FormationPlaceRemoval;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Replacement;
use SuperElf\Substitution;
use SuperElf\Transfer;

class Validator
{
    /**
     * @var list<SportsFormation>
     */
    protected array $availableFormations;

    public function __construct(Configuration $config)
    {
        /** @var list<string> $formations */
        $formations = $config->getArray('availableFormations');
        $this->initAvailableFormations($formations);
    }

    /**
     * @param list<string> $formationNames
     */
    protected function initAvailableFormations(array $formationNames): void
    {
        $this->availableFormations = [];
        foreach ($formationNames as $formationName) {
            $formation = new SportsFormation();
            new SportsFormationLine(
                $formation, FootballLine::GoalKeeper->value, (int)substr($formationName, 0, 1)
            );
            new SportsFormationLine($formation, FootballLine::Defense->value, (int)substr($formationName, 2, 1));
            new SportsFormationLine($formation, FootballLine::Midfield->value, (int)substr($formationName, 4, 1));
            new SportsFormationLine($formation, FootballLine::Forward->value, (int)substr($formationName, 6, 1));
            $this->availableFormations[] = $formation;
        }
    }

    /**
     * @param SportsFormation $sportsFormation
     * @throws \Exception
     */
    public function validate(SportsFormation $sportsFormation): void
    {
        try {
            if ($sportsFormation->getLines()->count() !== 4) {
                throw new \Exception('het aantal linies moet 4 zijn', E_ERROR);
            }
            $nrOfPersons = 0;
            foreach ($sportsFormation->getLines() as $formationLine) {
                $line = FootballLine::tryFrom($formationLine->getNumber());
                if ($line === null) {
                    throw new \Exception(
                        'onbekende linie "' . $formationLine->getNumber() . '" is aangetroffen',
                        E_ERROR
                    );
                }
                $nrOfPersons += $formationLine->getNrOfPersons();
            }
            if ($nrOfPersons !== 11) {
                throw new \Exception('het aantal teamleden moet 11 zijn', E_ERROR);
            }
            if (!$this->isAvailable($sportsFormation)) {
                throw new \Exception('de formatie "' . $sportsFormation->getName() . '" is niet beschikbaar', E_ERROR);
            }
        } catch (\Exception $e) {
            throw new \Exception('de formatie is onjuist: ' . $e->getMessage(), E_ERROR);
        }
    }

    public function validateTransferActions(PoolUser $poolUser): void
    {
        $transfers = $poolUser->getTransfers();
        $substitutions = $poolUser->getSubstitutions();

        $newFormation = $this->validateReplacements($poolUser);
        if( !$this->areAllPlacesWithoutTeamReplaced($poolUser)
            && ($transfers->count() > 0 || $substitutions->count() > 0)) {
            throw new \Exception('eerst moeten alle plekken met een speler zonder club worden vervangen');
        }

        $newFormation = $this->validateTransfers($poolUser, $newFormation);

        $this->validateSubstitutions($poolUser, $newFormation);
        // return $newFormation;
    }

//        try {
//            if ($sportsFormation->getLines()->count() !== 4) {
//                throw new \Exception('het aantal linies moet 4 zijn', E_ERROR);
//            }
//            $nrOfPersons = 0;
//            foreach ($sportsFormation->getLines() as $formationLine) {
//                $line = FootballLine::tryFrom($formationLine->getNumber());
//                if ($line === null) {
//                    throw new \Exception(
//                        'onbekende linie "' . $formationLine->getNumber() . '" is aangetroffen',
//                        E_ERROR
//                    );
//                }
//                $nrOfPersons += $formationLine->getNrOfPersons();
//            }
//            if ($nrOfPersons !== 11) {
//                throw new \Exception('het aantal teamleden moet 11 zijn', E_ERROR);
//            }
//            if (!$this->isAvailable($sportsFormation)) {
//                throw new \Exception('de formatie "' . $sportsFormation->getName() . '" is niet beschikbaar', E_ERROR);
//            }
//        } catch (\Exception $e) {
//            throw new \Exception('de formatie is onjuist: ' . $e->getMessage(), E_ERROR);
//        }
//    }

    public function validateReplacements(PoolUser $poolUser): SportsFormation {
        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('de formatie kan niet leeg zijn');
        }
        $sportsFormation = $assembleFormation->convertToBase();
        foreach($poolUser->getReplacements() as $replacement) {
            foreach($poolUser->getReplacements() as $replacementCompare) {
                if($replacementCompare !== $replacement
                    && $replacementCompare->getLineNumberOut() === $replacement->getLineNumberOut()
                    && $replacementCompare->getPlaceNumberOut() === $replacement->getPlaceNumberOut()) {
                    throw new \Exception('2 replacements for same formationplace');
                }
            }
            $this->validateReplacement($replacement);
            $sportsFormation = $this->calculateNewFormation($sportsFormation, $replacement);
        }
        return $sportsFormation;
    }

    protected function validateReplacement(Replacement $replacement): void {
        $transferPeriodStart = $replacement->getTransferPeriod()->getStartDateTime();
        $assembleFormation = $replacement->getPoolUser()->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('de formatie kan niet leeg zijn');
        }
        // check als formationplace echt een speler heeft zonder team
        $formationPlace = $assembleFormation->getPlace($replacement->getLineNumberOut(), $replacement->getPlaceNumberOut());
        if( $this->getTeam($formationPlace, $transferPeriodStart ) !== null ){
            throw new \Exception('de formatieplaats heeft al een team');
        }

        // 2 check if replacer has a valid player
        $oneTeamSimultaneous = new OneTeamSimultaneous();
        $playerIn = $oneTeamSimultaneous->getPlayer( $replacement->getPersonIn(), new \DateTimeImmutable() );
        if( $playerIn === null ) {
            throw new \Exception('de vervanger heeft geen geldige speler');
        }

        // 3 check als formationplace echt een speler heeft zonder team
        $this->validateSameTeam($replacement);
    }

    public function validateTransfers(PoolUser $poolUser, SportsFormation $sportsFormation): SportsFormation
    {
        foreach ($poolUser->getTransfers() as $transfer) {
            foreach ($poolUser->getTransfers() as $transferCompare) {
                if ($transferCompare !== $transfer
                    && $transferCompare->getLineNumberOut() === $transfer->getLineNumberOut()
                    && $transferCompare->getPlaceNumberOut() === $transfer->getPlaceNumberOut()) {
                    throw new \Exception('2 transfers for same formationplace');
                }
            }
            $this->validateTransfer($transfer);
            $sportsFormation = $this->calculateNewFormation($sportsFormation, $transfer);
        }
        return $sportsFormation;
    }

    protected function validateTransfer(Transfer $transfer): void {
        $transferPeriodStart = $transfer->getTransferPeriod()->getStartDateTime();
        $assembleFormation = $transfer->getPoolUser()->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('de formatie kan niet leeg zijn');
        }
        // check als formationplace echt een speler heeft zonder team
        $formationPlace = $assembleFormation->getPlace($transfer->getLineNumberOut(), $transfer->getPlaceNumberOut());
        if( $this->getTeam($formationPlace, $transferPeriodStart ) === null ){
            throw new \Exception('de formatieplaats heeft nog geen team');
        }

        // 2 check if transfer has a valid player
        $oneTeamSimultaneous = new OneTeamSimultaneous();
        $playerIn = $oneTeamSimultaneous->getPlayer( $transfer->getPersonIn(), new \DateTimeImmutable() );
        if( $playerIn === null ) {
            throw new \Exception('de vervanger heeft geen geldige speler');
        }

        // 3 check als formationplace echt een speler heeft zonder team
        $this->validateSameTeam($transfer);
    }

    public function validateSubstitutions(PoolUser $poolUser, SportsFormation $sportsFormation): void
    {
        foreach ($poolUser->getSubstitutions() as $substitution) {
            foreach ($poolUser->getSubstitutions() as $substitutionCompare) {
                if ($substitutionCompare !== $substitution
                    && $substitutionCompare->getLineNumberOut() === $substitution->getLineNumberOut()) {
                    throw new \Exception('2 wissels in dezelfde line');
                }
            }
            $this->validateSubstitution($substitution);
        }
    }

    protected function validateSubstitution(Substitution $substitution): void {
        $assembleFormation = $substitution->getPoolUser()->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('de formatie kan niet leeg zijn');
        }
        // check als formationplace echt een speler heeft zonder team
        $formationPlace = $assembleFormation->getPlace($substitution->getLineNumberOut(), $substitution->getPlaceNumberOut());
        if( $formationPlace->getNumber() === 0 ) {
            throw new \Exception('deze plaats is al een wissel');
        }
    }

    public function areAllPlacesWithoutTeamReplaced(PoolUser $poolUser): bool {
        $replacements = $poolUser->getReplacements()->toArray();

        $transferPeriodStart = $poolUser->getPool()->getTransferPeriod()->getStartDateTime();

        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('kies eerst een startformatie');
        }
        $placesWithoutTeam =  $this->getPlacesWithoutTeam($assembleFormation,$transferPeriodStart);

        if( count($replacements) > count($placesWithoutTeam) ) {
            throw new \Exception('te veel vervangingen voor de formatieplekken');
        }
        return count($placesWithoutTeam) === count($replacements);
    }

    /**
     * @param Formation $formation
     * @param \DateTimeImmutable $dateTime
     * @return list<Place>
     */
    public function getPlacesWithoutTeam(Formation $formation, \DateTimeImmutable $dateTime): array
    {
        return array_values(array_filter(
            $formation->getPlaces(),
            function(FormationPlace $place) use($dateTime): bool {
                return $this->getTeam($place, $dateTime ) === null;
            }
        ));
    }

    public function getTeam(FormationPlace $place, \DateTimeImmutable $dateTime): Team|null
    {
        $oneTeamSim = new OneTeamSimultaneous();
        $s11Player = $place->getPlayer();
        if( $s11Player === null ) {
            return null;
        }
        $player = $oneTeamSim->getPlayer($s11Player->getPerson(), $dateTime );
        return $player?->getTeam();
    }

    public function validateSameTeam(Replacement|Transfer $editAction): void
    {
        $poolUser = $editAction->getPoolUser();
        $transferPeriodStart = $poolUser->getPool()->getTransferPeriod()->getStartDateTime();

        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation === null ) {
            throw new \Exception('de formatie is leeg');
        }
        $formationPlace = $assembleFormation->getPlace($editAction->getLineNumberOut(), $editAction->getPlaceNumberOut());

        $oneTeamSim = new OneTeamSimultaneous();
        $s11PlayerOut = $formationPlace->getPlayer();
        if( $s11PlayerOut === null ) {
            throw new \Exception('de formatieplaats heeft geen speler');
        }
        $players = $s11PlayerOut->getPlayersDescendingStart();
        $playerOut = array_shift($players);
        if( $playerOut === null ) {
            throw new \Exception('de formatieplaats heeft geen speler');
        }

        $playerIn = $oneTeamSim->getPlayer($editAction->getPersonIn(), $transferPeriodStart );
        if( $playerIn === null ) {
            throw new \Exception('de vervanger heeft geen team');
        }

        if( $playerOut->getTeam() !== $playerIn->getTeam() ) {
            throw new \Exception('teams('.$playerOut->getTeam().', <= ' . $playerIn->getTeam() . ') zijn ongelijk');
        }
    }

    public function calculateNewFormation(SportsFormation $formation, Replacement|Transfer $editAction): SportsFormation {
        $dateTime = $editAction->getTransferPeriod()->getStartDateTime();
        $player = (new OneTeamSimultaneous())->getPlayer($editAction->getPersonIn(), $dateTime);
        if( $player === null ) {
            throw new \Exception('de speler van de aanpassing met bij een team spelen ');
        }
        return (new FootballEditor())->addPersonToLine($formation, FootballLine::from($player->getLine()) );
    }

    /**
     * @param SportsFormation $sportsFormation
     * @return bool
     */
    public function isAvailable(SportsFormation $sportsFormation): bool
    {
        $name = $sportsFormation->getName();
        foreach ($this->availableFormations as $availableFormation) {
            if ($availableFormation->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<SportsFormation>
     */
    public function getAvailable(): array
    {
        return $this->availableFormations;
    }

}
