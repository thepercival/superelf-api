<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HelpCommand extends Command
{
    /**
     * @param ContainerInterface $container
     * @param array<int, string> $commandKeys
     */
    public function __construct(protected ContainerInterface $container, protected array $commandKeys)
    {
        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:help')
            // the short description shown while running "php bin/console list"
            ->setDescription('list the commands')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('list the commands')
            ->addArgument(
                'commandfilter',
                InputArgument::OPTIONAL,
                'show only this command'
            );

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLogger($input, 'command-help');
        $commandFilter = $this->getCommandFilterFromInput($input);

        foreach ($this->commandKeys as $commandKey) {
            /** @var Command $command */
            $command = $this->container->get($commandKey);
            if ($commandFilter !== null && $command->getName() !== $commandFilter) {
                continue;
            }
            echo $commandKey . " (" . $command->getDescription() . ")" . PHP_EOL;
            foreach ($command->getDefinition()->getArguments() as $argument) {
                echo "  " . $argument->getName() . " (" . $argument->getDescription() . ")" . PHP_EOL;
            }
            foreach ($command->getDefinition()->getOptions() as $option) {
                echo " --" . $option->getName() . " (" . $option->getDescription() . ")" . PHP_EOL;
            }
            echo PHP_EOL;
        }
        return 0;
    }

    protected function getCommandFilterFromInput(InputInterface $input): string|null
    {
        /** @var string|null $commandFilter */
        $commandFilter =  $input->getArgument('commandfilter');
        return $commandFilter;
    }
}
