<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Selective\Config\Configuration;
use Sports\Formation as SportsFormation;
use Sports\Sport\FootballLine;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Formation\Place\Removal as FormationPlaceRemoval;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

final class Editor
{
    protected Validator $validator;


    public function __construct(Configuration $config, protected bool $nrOfPersonsCanBeZero)
    {
        $this->validator = new Validator($config);
    }

    public function createAssemble(PoolUser $poolUser, SportsFormation $sportsFormation): Formation
    {
        $this->validate($sportsFormation);

        $assembleFormation = $poolUser->getAssembleFormation();
        if ($assembleFormation !== null) {
            return $assembleFormation;
        }
        $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();
        $formation = $this->create($assemblePeriod, $sportsFormation);
        $poolUser->setAssembleFormation($formation);
        return $formation;
    }

    public function createTransfer(PoolUser $poolUser, SportsFormation $sportsFormation): Formation
    {
        $this->validate($sportsFormation);

        $transferFormation = $poolUser->getTransferFormation();
        if ($transferFormation !== null) {
            return $transferFormation;
        }
        $transferPeriod = $poolUser->getPool()->getAssemblePeriod();
        $formation = $this->create($transferPeriod, $sportsFormation);
        $poolUser->setTransferFormation($formation);
        return $formation;
    }

    protected function create(AssemblePeriod|TransferPeriod $editPeriod, SportsFormation $sportsFormation): Formation
    {
        $formation = new Formation($editPeriod->getViewPeriod());
        foreach ($sportsFormation->getLines() as $sportsFormationLine) {
            $line = new FormationLine($formation, $sportsFormationLine->getNumber());
            for ($placeNumber = 0; $placeNumber <= $sportsFormationLine->getNrOfPersons(); $placeNumber++) {
                new FormationPlace($line, null, $placeNumber, 0);
            }
        }
        return $formation;
    }

    public function copyFormation(Formation $sourceFormation): Formation
    {
        $newFormation = new Formation($sourceFormation->getViewPeriod());
        foreach ($sourceFormation->getLines() as $sourceFormationLine) {
            $newLine = new FormationLine($newFormation, $sourceFormationLine->getNumber());
            foreach( $sourceFormationLine->getPlaces() as $sourceFormationPlace) {
                $formationPlace = new FormationPlace(
                    $newLine, $sourceFormationPlace->getPlayer(),
                    $sourceFormationPlace->getNumber(), $sourceFormationPlace->getMarketValue());
                $formationPlace->setPenaltyPoints($sourceFormationPlace->getPenaltyPoints() );
            }
        }
        return $newFormation;
    }


    /**
     * @param Formation $formation
     * @param SportsFormation $newSportFormation
     * @return list<FormationPlaceRemoval>
     */
    public function removeAssemble(Formation $formation, SportsFormation $newSportFormation): array
    {
        $this->validate($newSportFormation);

        $removedPlaceRemovals = [];
        foreach ($formation->getLines() as $formationLine) {
            $newSportFormationLine = $newSportFormation->getLine($formationLine->getNumber());
            $diffPlaces = ($formationLine->getPlaces()->count() - 1) - $newSportFormationLine->getNrOfPersons();
            for ($i = 0 ; $i < $diffPlaces ; $i++) {
                $removedPlaceRemovals[] = $this->removePlace($formationLine);
            }
        }
        return $removedPlaceRemovals;
    }

    /**
     * @param Formation $formation
     * @param SportsFormation $newSportFormation
     * @return list<FormationPlace>
     */
    public function addAssemble(Formation $formation, SportsFormation $newSportFormation): array
    {
        $this->validate($newSportFormation);

        $addedPlaces = [];
        foreach ($formation->getLines() as $formationLine) {
            $newSportFormationLine = $newSportFormation->getLine($formationLine->getNumber());
            $diffPlaces = $newSportFormationLine->getNrOfPersons() - ($formationLine->getPlaces()->count() - 1);
            for ($i = 0 ; $i < $diffPlaces ; $i++) {
                $addedPlaces[] = new FormationPlace($formationLine, null, null, 0);
            }
        }

        return $addedPlaces;
    }

    protected function removePlace(FormationLine $formationLine): FormationPlaceRemoval
    {
        $lastPlace = $this->getLastStartingPlace($formationLine);
        $formationLine->getPlaces()->removeElement($lastPlace);
        return new FormationPlaceRemoval($lastPlace, $lastPlace->getPlayer());
    }

    public function getLastStartingPlace(FormationLine $formationLine): FormationPlace
    {
        $lastPlace = $formationLine->getStartingPlaces()->last();
        if ($lastPlace === false) {
            throw new \Exception('the formationLine "' . $formationLine->getNumber() . '" has no places', E_ERROR);
        }
        return $lastPlace;
    }

    public function getLastStartingPlaceWithoutPlayer(FormationLine $formationLine): FormationPlace|null
    {
        $places = $formationLine->getStartingPlaces()->toArray();
        while ($lastPlace = array_pop($places)) {
            if ($lastPlace->getPlayer() === null) {
                return $lastPlace;
            }
        }
        return null;
    }


//    /**
//     * @param array<int, int> $formationData
//     * @return string
//     */
//    public function getName(array $formationData): string
//    {
//        return join('-', $formationData);
//    }

    /**
     * @param SportsFormation $sportsFormation
     * @throws \Exception
     */
    protected function validate(SportsFormation $sportsFormation): void
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
            if ($this->nrOfPersonsCanBeZero === false && $nrOfPersons !== 11) {
                throw new \Exception('het aantal teamleden moet 11 zijn', E_ERROR);
            }
            if (!$this->validator->isAvailable($sportsFormation)) {
                throw new \Exception('de formatie "' . $sportsFormation->getName() . '" is niet beschikbaar', E_ERROR);
            }
        } catch (\Exception $e) {
            throw new \Exception('de formatie is onjuist: ' . $e->getMessage(), E_ERROR);
        }
    }
}
