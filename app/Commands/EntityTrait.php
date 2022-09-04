<?php

namespace App\Commands;

use SportsImport\Entity;
use Symfony\Component\Console\Input\InputInterface;

trait EntityTrait
{
    protected function getEntityFromInput(InputInterface $input): int
    {
        $objectType = (string)$input->getArgument('objectType');
        $message = 'objectType "' . $objectType . '" kan niet worden opgehaald uit externe bronnen';

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
            case 'games-basics':
                return Entity::GAMES_BASICS;
            case 'games-complete':
                return Entity::GAMES_COMPLEET;
            case 'game':
                return Entity::GAME;
            case 'players':
                return Entity::PLAYERS;
            case 'transfers':
                return Entity::TRANSFERS;
        }
        $this->getLogger()->error($message);
        throw new \Exception($message, E_ERROR);
    }
}
