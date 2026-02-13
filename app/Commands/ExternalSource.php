<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Sports\Repositories\AgainstGameRepository;
use SportsImport\Attachers\AgainstGameAttacher;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Implementation as ExternalSourceImplementation;
use SportsImport\Repositories\AttacherRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class ExternalSource extends Command
{
    use EntityTrait;

    protected ExternalSourceFactory $externalSourceFactory;
    protected AgainstGameRepository $againstGameRepos;
    /** @var AttacherRepository<AgainstGameAttacher>  */
    protected AttacherRepository $againstGameAttacherRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var ExternalSourceFactory $externalSourceFactory */
        $externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->externalSourceFactory = $externalSourceFactory;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $metadata = $entityManager->getClassMetadata(AgainstGameAttacher::class);
        $this->againstGameAttacherRepos = new AttacherRepository($entityManager, $metadata);

        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('externalSource', InputArgument::REQUIRED, 'for example sofascore');
        $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or competitions');

        $this->addOption('sport', null, InputOption::VALUE_OPTIONAL, 'the name of the sport');
        $this->addOption('association', null, InputOption::VALUE_OPTIONAL, 'the name of the association');
        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'the name of the league');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, 'the name of the season');
        $this->addOption('no-game-cache', null, InputOption::VALUE_NONE, 'no-game-cache');

        parent::configure();
    }

    protected function getExternalSourceImplFromInput(InputInterface $input): ExternalSourceImplementation|null
    {
        $externalSourceName = (string)$input->getArgument('externalSource');
        return $this->externalSourceFactory->createByName($externalSourceName);
    }

    protected function getGameCacheOptionFromInput(InputInterface $input): bool
    {
        return $input->hasOption('no-game-cache') && filter_var(
            $input->getOption('no-game-cache'),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
