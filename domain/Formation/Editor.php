<?php
declare(strict_types=1);

namespace SuperElf\Formation;

use Sports\Sport\Custom as CustomSport;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\Formation;
use Sports\Formation as SportsFormation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation\Place as FormationPlace;

class Editor
{
    public function __construct(
        protected ActiveConfigService $activeConfigService
    ) {
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

    protected function create(AssemblePeriod|TransferPeriod $editPeriod, SportsFormation $sportsFormation): Formation
    {
        $formation = new Formation($editPeriod->getViewPeriod());
        foreach ($sportsFormation->getLines() as $sportsFormationLine) {
            $line = new FormationLine($formation, $sportsFormationLine->getNumber());
            for ($placeNumber = 0 ; $placeNumber <= $sportsFormationLine->getNrOfPersons() ; $placeNumber++) {
                new FormationPlace($line, null, $placeNumber);
            }
        }
        return $formation;
    }

    /**
     * @param Formation $formation
     * @param SportsFormation $newSportFormation
     * @return list<FormationPlace>
     */
    public function updateAssemble(Formation $formation, SportsFormation $newSportFormation): array
    {
        $this->validate($newSportFormation);

        $removedPlaces = [];
        foreach ($formation->getLines() as $formationLine) {
            $newSportFormationLine = $newSportFormation->getLine($formationLine->getNumber());
            $diffPlaces = $newSportFormationLine->getNrOfPersons() - ($formationLine->getPlaces()->count() - 1);
            for ($i = 0 ; $i < $diffPlaces ; $i++) {
                new FormationPlace($formationLine, null);
            }
            $diffPlaces = ($formationLine->getPlaces()->count() - 1) - $newSportFormationLine->getNrOfPersons();
            for ($i = 0 ; $i < $diffPlaces ; $i++) {
                $removedPlaces[] = $this->removePlace($formationLine);
            }
        }
        return $removedPlaces;
    }

    protected function removePlace(FormationLine $formationLine): FormationPlace
    {
        $lastPlace = $this->getLastStartingPlace($formationLine);
        $lastPlacePlayer = $lastPlace->getPlayer();
        if ($lastPlacePlayer !== null) {
            $lastPlaceWithoutPlayer = $this->getLastStartingPlaceWithoutPlayer($formationLine);
            if ($lastPlaceWithoutPlayer !== null) {
                $lastPlaceWithoutPlayer->setPlayer($lastPlacePlayer);
            }
        }
        $formationLine->getPlaces()->removeElement($lastPlace);
        return $lastPlace;
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
                $lineNumber = $formationLine->getNumber();
                if (($lineNumber & CustomSport::Football_Line_All) !== $lineNumber) {
                    throw new \Exception('de onbekende linie "' . $lineNumber . '" is aangetroffen', E_ERROR);
                }
                $nrOfPersons += $formationLine->getNrOfPersons();
            }
            if ($nrOfPersons !== 11) {
                throw new \Exception('het aantal teamleden moet 11 zijn', E_ERROR);
            }
            if (!$this->activeConfigService->isAvailable($sportsFormation)) {
                throw new \Exception('de formatie "' . $sportsFormation->getName() . '" is niet beschikbaar', E_ERROR);
            }
        } catch (\Exception $e) {
            throw new \Exception('de formatie is onjuist: ' . $e->getMessage(), E_ERROR);
        }
    }
}
