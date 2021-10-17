<?php
declare(strict_types=1);

namespace App\Commands;

use SportsImport\Entity;
use SportsImport\ExternalSource\ApiHelper;
use SportsImport\ExternalSource\CacheInfo;
use SportsImport\ExternalSource\CompetitionDetails;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\Implementation as ExternalSourceImplementation;
use Sports\Competition\Repository as CompetitionRepository;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Psr\Container\ContainerInterface;
use App\Command;

use Symfony\Component\Console\Input\InputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource as ExternalSourceBase;

abstract class ExternalSource extends Command
{
    protected ExternalSourceFactory $externalSourceFactory;

    public function __construct(ContainerInterface $container, string $loggerName)
    {
        /** @var ExternalSourceFactory externalSourceFactory */
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        parent::__construct($container, $loggerName);
    }

    protected function getExternalSourceImplFromInput(InputInterface $input): ExternalSourceImplementation|null
    {
        $externalSourceName = (string)$input->getArgument('externalSource');
        return $this->externalSourceFactory->createByName($externalSourceName);
    }

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
           default:
                $message = 'objectType "' . $objectType . '" kan niet worden opgehaald uit externe bronnen';
                $this->logger->error($message);
            }
        return 0;
    }
}
