<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\Repositories\PoolRepository;
use App\Services\PoolAdministrator;
use Psr\Container\ContainerInterface;
use SuperElf\Pool;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RemovePoolsCommand extends Command
{
    private PoolRepository $poolRepos;
    private PoolAdministrator $poolAdmin;

    public function __construct(ContainerInterface $container)
    {
        /** @var PoolRepository $poolRepos */
        $poolRepos = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepos;

        /** @var PoolAdministrator $poolAdmin */
        $poolAdmin = $container->get(PoolAdministrator::class);
        $this->poolAdmin = $poolAdmin;
        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('app:remove-pools')
            ->setDescription('Removes pools and their competitions for one season')
            ->addOption('league', null, InputOption::VALUE_REQUIRED, 'League name')
            ->addOption('season', null, InputOption::VALUE_REQUIRED, 'Season name')
            ->addOption(
                'pool',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Pool name; repeat this option for multiple pools'
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Remove without confirmation');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->initLogger($input, 'command-remove-pools');

        try {
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $poolNames = $this->getPoolNames($input);
            $pools = $this->findPools(
                $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]),
                $poolNames
            );

            if (!$this->isForced($input)) {
                $question = new ConfirmationQuestion(
                    'Remove ' . implode(', ', array_map(fn(Pool $pool): string => $pool->getName(), $pools)) .
                        ' for ' . $competitionConfig->getSeason()->getName() . '? [y/N] ',
                    false
                );
                /** @var QuestionHelper $questionHelper */
                $questionHelper = $this->getHelper('question');
                if (!$questionHelper->ask($input, $output, $question)) {
                    $output->writeln('No pools removed.');
                    return SymfonyCommand::SUCCESS;
                }
            }

            foreach ($pools as $pool) {
                $poolName = $pool->getName();
                $poolId = $pool->getId();
                $this->poolAdmin->removePool($pool);
                $logger->info('removed pool "' . $poolName . '" (' . (string)$poolId . ')');
            }
            return SymfonyCommand::SUCCESS;
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage());
            return SymfonyCommand::FAILURE;
        }
    }

    /**
     * @return non-empty-list<string>
     */
    private function getPoolNames(InputInterface $input): array
    {
        $poolNames = $input->getOption('pool');
        if (!is_array($poolNames)) {
            throw new \Exception('invalid --pool option');
        }
        $poolNames = array_values(array_filter(
            $poolNames,
            fn(mixed $poolName): bool => is_string($poolName) && $poolName !== ''
        ));
        if ($poolNames === []) {
            throw new \Exception('provide at least one --pool option');
        }
        /** @var non-empty-list<string> $poolNames */
        return $poolNames;
    }

    /**
     * @param list<Pool> $availablePools
     * @param non-empty-list<string> $poolNames
     * @return non-empty-list<Pool>
     */
    private function findPools(array $availablePools, array $poolNames): array
    {
        $pools = [];
        foreach ($poolNames as $poolName) {
            $pool = null;
            foreach ($availablePools as $availablePool) {
                if (strcasecmp($availablePool->getName(), $poolName) === 0) {
                    $pool = $availablePool;
                    break;
                }
            }
            if ($pool === null) {
                throw new \Exception('pool "' . $poolName . '" not found for the selected league and season');
            }
            if (!in_array($pool, $pools, true)) {
                $pools[] = $pool;
            }
        }
        if ($pools === []) {
            throw new \LogicException('no pools selected');
        }
        return $pools;
    }

    private function isForced(InputInterface $input): bool
    {
        return $input->getOption('force') === true;
    }
}
