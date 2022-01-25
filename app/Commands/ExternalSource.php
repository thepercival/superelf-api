<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Psr\Container\ContainerInterface;
use SportsImport\ExternalSource\ApiHelper;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Implementation as ExternalSourceImplementation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class ExternalSource extends Command
{
    use EntityTrait;
    protected ExternalSourceFactory $externalSourceFactory;

    public function __construct(ContainerInterface $container)
    {
        /** @var ExternalSourceFactory $externalSourceFactory */
        $externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->externalSourceFactory = $externalSourceFactory;

        parent::__construct($container);
    }

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
