<?php

declare(strict_types=1);

namespace SuperElf\Formation;

use Selective\Config\Configuration;
use Sports\Formation as SportsFormation;
use Sports\Formation\Line as SportsFormationLine;
use Sports\Sport\FootballLine;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Pool\User as PoolUser;

class Editor
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
    public function removeAssemble(Formation $formation, SportsFormation $newSportFormation): array
    {
        $this->validate($newSportFormation);

        $removedPlaces = [];
        foreach ($formation->getLines() as $formationLine) {
            $newSportFormationLine = $newSportFormation->getLine($formationLine->getNumber());
            $diffPlaces = ($formationLine->getPlaces()->count() - 1) - $newSportFormationLine->getNrOfPersons();
            for ($i = 0 ; $i < $diffPlaces ; $i++) {
                $removedPlaces[] = $this->removePlace($formationLine);
            }
        }
        return $removedPlaces;
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
                $addedPlaces[] = new FormationPlace($formationLine, null);
            }
        }

        return $addedPlaces;
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
