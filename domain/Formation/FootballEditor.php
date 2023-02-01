<?php

namespace SuperElf\Formation;

use Sports\Formation as SportsFormation;
use Sports\Formation\Line as SportsFormationLine;
use Sports\Sport\FootballLine;

class FootballEditor
{
    public function addPersonToLine(SportsFormation $formation, FootballLine $footballLine): SportsFormation {
        $newFormation = new SportsFormation();
        foreach( $formation->getLines() as $formationLine) {

            $nrOfPersons = $formationLine->getNrOfPersons();
            if( $footballLine->value === $formationLine->getNumber() ) {
                $nrOfPersons++;
            }
            new SportsFormationLine($newFormation, $formationLine->getNumber(),  $nrOfPersons);
        }
        return $formation;
    }

    public function removePersonFromLine(SportsFormation $formation, FootballLine $footballLine): SportsFormation {
        $newFormation = new SportsFormation();
        foreach( $formation->getLines() as $formationLine) {

            $nrOfPersons = $formationLine->getNrOfPersons();
            if( $footballLine->value === $formationLine->getNumber() ) {
                $nrOfPersons--;
            }
            new SportsFormationLine($newFormation, $formationLine->getNumber(),  $nrOfPersons);
        }
        return $formation;
    }
}