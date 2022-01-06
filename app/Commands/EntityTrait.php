<?php

namespace App\Commands;

use SportsImport\Entity;
use Symfony\Component\Console\Input\InputInterface;

trait EntityTrait
{
    protected function getEntityFromInput(InputInterface $input): int
    {
        $objectType = (string)$input->getArgument('objectType');

        switch ($objectType) {
            case 'sports':
                return Entity::SPORTS;
            case 'associations':
                return Entity::ASSOCIATIONS;
            case 'seasons':
                return Entity::SEASONS;
            case 'leagues':
                return Entity::LEAGUES;
            case 'competitions':
                return Entity::COMPETITIONS;
            case 'structure':
                return Entity::STRUCTURE;
            case 'teamcompetitors':
                return Entity::TEAMCOMPETITORS;
            case 'teams':
                return Entity::TEAMS;
            case 'games':
                return Entity::GAMES;
            case 'game':
                return Entity::GAMEDETAILS;
            case 'players':
                return Entity::PLAYERS;
            default:
                $message = 'objectType "' . $objectType . '" kan niet worden opgehaald uit externe bronnen';
                $this->getLogger()->error($message);
        }
        return 0;
    }
}
