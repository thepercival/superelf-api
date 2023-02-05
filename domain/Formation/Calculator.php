<?php

namespace SuperElf\Formation;

use Sports\Sport\FootballLine;
use Sports\Team;
use Sports\Team\Player;
use SuperElf\Formation\Calculator as FormationCalculator;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation as S11Formation;
use SuperElf\Formation\Line as S11FormationLine;
use SuperElf\Formation\Place as S11FormationPlace;
use SuperElf\Player as S11Player;
use SuperElf\Replacement;
use SuperElf\Substitution;
use SuperElf\Transfer;

class Calculator
{
    public function getCurrentFormation(PoolUser $poolUser): S11Formation|null {
        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation === null) {
            return null;
        }
        $newFormation = $assembleFormation;
//        $$this->toConsole('after assemble', newFormation);        
        foreach( $poolUser->getReplacements() as $replacement) {
            $newFormation = $this->processReplacement($newFormation, $replacement);
        }
//        $$this->toConsole('after replace', newFormation);        
        
        foreach( $poolUser->getTransfers() as $transfer) {
            $newFormation = $this->processTransfer($newFormation, $transfer);
        }
//        $$this->toConsole('after transfer', newFormation);        
        
        foreach( $poolUser->getSubstitutions() as $substitution) {
            $newFormation = $this->processSubstitution($newFormation, $substitution);
        }
//        $$this->toConsole('after substitute', newFormation);        
        return $newFormation;
    }    

    public function processReplacement(S11Formation $currentFormation, Replacement $replacement): S11Formation {
        if( $replacement->getLineNumberOut() === FootballLine::from($replacement->getPlayerIn()->getLine()) ) {
            return $this->updatePlace($currentFormation,$replacement);
        }
        $viewPeriod = $replacement->getTransferPeriod()->getViewPeriod();
        //    console->log('start processReplacement');
        // remove place
        $currentFormation = $this->removePlace($currentFormation, $replacement->getLineNumberOut(), $replacement->getPlaceNumberOut(), $viewPeriod );

        // add Player To End of line
        $currentFormation = $this->addPlace($currentFormation, $replacement->getPlayerIn(), false, $viewPeriod );
        return $currentFormation;
    }

    public function processTransfer(S11Formation $currentFormation, Transfer $transfer): S11Formation {

        if( $transfer->getLineNumberOut() === FootballLine::from($transfer->getPlayerIn()->getLine()) ) {
            return $this->updatePlace($currentFormation, $transfer);
        }
        $viewPeriod = $transfer->getTransferPeriod()->getViewPeriod();
//        console->log('start processTransfer');
        // remove place
        $currentFormation = $this->removePlace($currentFormation, $transfer->getLineNumberOut(), $transfer->getPlaceNumberOut(), $viewPeriod );

        // add Player To End of line        
        return $this->addPlace($currentFormation, $transfer->getPlayerIn(), false, $viewPeriod );
    }

    public function getPlayerWithTeam(S11Formation $formation, Team $team): Player|null {
        $playersWithTeam = array_filter($formation->getTeamPlayers(), function(Player $player) use ($team) : bool {
            return $player->getTeam() === $team;
        });
        return array_shift($playersWithTeam);
    }

    public function processSubstitution(S11Formation $currentFormation, Substitution $substitution): S11Formation {

//    console->log('start processSubstitution');
        $viewPeriod = $substitution->getTransferPeriod()->getViewPeriod();
        $place = $currentFormation->getPlace($substitution->getLineNumberOut(), $substitution->getPlaceNumberOut());
        $substitutePlace = $currentFormation->getPlace($substitution->getLineNumberOut(), 0);
        // remove
        $currentFormation = $this->removePlace($currentFormation, $substitution->getLineNumberOut(), $place->getNumber(), $viewPeriod );
        // remove substitute
        $currentFormation = $this->removePlace($currentFormation, $substitution->getLineNumberOut(), $substitutePlace->getNumber(), $viewPeriod );

        $player = $place->getPlayer()?->getMostRecentPlayer();
        $playerSubstitute = $substitutePlace->getPlayer()?->getMostRecentPlayer();
        if( $player === null || $playerSubstitute === null) {
            throw new \Exception('all places should have players');
        }
        $currentFormation = $this->addPlace($currentFormation, $playerSubstitute, false, $viewPeriod ); // add place
        return $this->addPlace($currentFormation, $player, true, $viewPeriod ); // add substitution
    }

    private function updatePlace(S11Formation $currentFormation, Replacement|Transfer $editAction): S11Formation {

        $newFormation = new S11Formation($editAction->getTransferPeriod()->getViewPeriod());
        foreach( $currentFormation->getLines() as $currentLine) {
            $line = new S11FormationLine($newFormation, $currentLine->getNumber());
            foreach( $currentLine->getPlaces() as $currentPlace) {
                $currentS11Player = $currentPlace->getPlayer();
                if( $currentS11Player === null) {
                    throw new \Exception('place should have a player');
                }
                $newS11Player = $currentS11Player;
                if( $editAction->getLineNumberOut() ===  FootballLine::from($line->getNumber())
                    && $editAction->getPlaceNumberOut() === $currentPlace->getNumber()  ) {
                    $playerIn = $editAction->getPlayerIn();
                    $newS11Player = new S11Player($currentFormation->getViewPeriod(), $playerIn->getPerson());
                }                
                new S11FormationPlace(
                    $line,
                    $newS11Player,
                    $currentPlace->getNumber()
                );                
            }
        }
        return $newFormation;
    }

    private function addPlace(S11Formation $currentFormation, Player $player, bool $asSubstitute, ViewPeriod $viewPeriod): S11Formation {
        $playerLineNumber = $player->getLine();
        $newFormation = new S11Formation($viewPeriod);
        foreach( $currentFormation->getLines() as $currentLine) {
            $line = new S11FormationLine($newFormation, $currentLine->getNumber());
            foreach( $currentLine->getPlaces() as $currentPlace) {
                new S11FormationPlace(
                    $line, $currentPlace->getPlayer(), $currentPlace->getNumber()
                );
            }
            if( $line->getNumber() === $playerLineNumber) {
                $newS11Player = new S11Player(
                    $currentFormation->getViewPeriod(), $player->getPerson()
                );
                $placeNumber = $asSubstitute ? 0 : count($currentLine->getStartingPlaces()) + 1;
                // console->log('add Place to lineNr ' + line->getNumber() + ' as placeNr ' + placeNumber);
                new S11FormationPlace(
                    $line, $newS11Player, $placeNumber
                );
            }  
        }
        return $newFormation;
    }

    private function removePlace(S11Formation $currentFormation, FootballLine $lineNumber, int $placeNumber, ViewPeriod $viewPeriod): S11Formation {
        if( $placeNumber === 0 ) {
            return $this->removeSubstitutePlace($currentFormation, $lineNumber, $viewPeriod);
        }
        return $this->removeStartingPlace($currentFormation, $lineNumber, $placeNumber, $viewPeriod);
    }

    private function removeStartingPlace(
        S11Formation $currentFormation, FootballLine $lineNumber, int $placeNumber, ViewPeriod $viewPeriod): S11Formation {
        $newFormation = new S11Formation($viewPeriod);
        foreach( $currentFormation->getLines() as $currentLine) {
            $line = new S11FormationLine($newFormation, $currentLine->getNumber());
            $removed = false;
            foreach( $currentLine->getPlaces() as $currentPlace) {
                if( FootballLine::from($line->getNumber()) === $lineNumber
                    && $currentPlace->getNumber() === $placeNumber ) {
                    $removed = true;
                    continue;
                }
                $newPlaceNumber = $currentPlace->getNumber();
                    if( $removed && $currentPlace->getNumber() > 0 ) {
                        $newPlaceNumber--;
                    }
                    new S11FormationPlace($line, $currentPlace->getPlayer(), $newPlaceNumber);
            }
        }
        return $newFormation;
    }

    private function removeSubstitutePlace(S11Formation $currentFormation, FootballLine $lineNumber, ViewPeriod $viewPeriod): S11Formation {
        $newFormation = new S11Formation($viewPeriod);
        foreach( $currentFormation->getLines() as $currentLine) {
            $line = new S11FormationLine($newFormation, $currentLine->getNumber());

            if( FootballLine::from($line->getNumber()) === $lineNumber ) {
                $places = $currentLine->getStartingPlaces();
            } else {
                $places = $currentLine->getPlaces();
            }
            foreach( $places as $currentPlace) {
                new S11FormationPlace($line, $currentPlace->getPlayer(), $currentPlace->getNumber());
            }
        }
        return $newFormation;
    }

//    private function validateAvailableTeam(S11Formation $formation, $transfer): void {
//        $teams = ($this->>getFormationTeams($formation);
//    }

    /**
     * @param S11Formation $formation
     * @return list<Team>
     */
    public function getFormationTeams(S11Formation $formation): array {
        return array_map(function(Player $player): Team {
            return $player->getTeam();
        }, $formation->getTeamPlayers() );
    }

//    const teams = formation.getPlayers().map((s11Player: S11Player): Team|undefined => {
//            return this.getTeamDescendingStart(s11Player);
//        });

    public function getTeamDescendingStart(S11Player|null $s11Player): Team|null {
        // console->log(s11Player);
        return $s11Player?->getMostRecentPlayer()?->getTeam();
    }

//    private function toConsole(header: string, currentFormation: S11Formation): void {
//    $linesAsString = currentFormation->getLines()->map((formationLine: S11FormationLine): string => {
//        return '' + formationLine->getStartingPlaces()->length;
//    });
//        console->log( header + ' : ' + linesAsString->join('-') );
//    }
}