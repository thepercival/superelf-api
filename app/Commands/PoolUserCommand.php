<?php

namespace App\Commands;

use App\Command;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Formation as S11Formation;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:pooluser
 *      show --season=2022/2023 --poolName='kamp duim' --userName='boy' --loglevel=200
 */
class PoolUserCommand extends Command
{
    private string $customName = 'pooluser';

//    private FormationValidator $formationValidator;
//    private bool $dryRun = false;
//
//    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PoolRepository $poolRepos;
    protected PoolCollectionRepository $poolCollectionRepos;
    protected UserRepository $userRepos;
//    protected S11FormationRepository $s11FormationRepos;
//    protected S11PlayerSyncer $s11PlayerSyncer;

    public function __construct(ContainerInterface $container)
    {
//        /** @var CompetitionConfigRepository $competitionConfigRepos */
//        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
//        $this->competitionConfigRepos = $competitionConfigRepos;
//
        /** @var PoolRepository $poolRepository */
        $poolRepository = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepository;

        /** @var PoolCollectionRepository $poolCollectionRepository */
        $poolCollectionRepository = $container->get(PoolCollectionRepository::class);
        $this->poolCollectionRepos = $poolCollectionRepository;

        /** @var UserRepository $userRepos */
        $userRepos = $container->get(UserRepository::class);
        $this->userRepos = $userRepos;
//
//        /** @var S11FormationRepository $s11FormationRepos */
//        $s11FormationRepos = $container->get(S11FormationRepository::class);
//        $this->s11FormationRepos = $s11FormationRepos;
//
//        /** @var S11PlayerSyncer $s11PlayerSyncer */
//        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
//        $this->s11PlayerSyncer = $s11PlayerSyncer;

        parent::__construct($container);

//        $this->formationValidator = new FormationValidator($this->config);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:' . $this->customName)
            ->setDescription('action for the poolUser')
            ->setHelp('action for the poolUser');

        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('pool', null, InputOption::VALUE_REQUIRED, 'kamp duim');
        $this->addOption('user', null, InputOption::VALUE_REQUIRED, 'coen');
        // $this->addOption('dry-run', null, InputOption::VALUE_NONE);


        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevel($input),
            $this->getStreamDef($input, $loggerName),
            $loggerName,
        );

        try {
            $seasonName = $this->inputHelper->getStringFromInput($input, 'season');
            $poolName = $this->inputHelper->getStringFromInput($input, 'pool');
            $userName = $this->inputHelper->getStringFromInput($input, 'user');

            $season = $this->seasonRepos->findOneBy(['name' => $seasonName]);
            $association = $this->associationRepos->findOneBy(['name' => $poolName]);
            $user = $this->userRepos->findOneBy(['name' => $userName]);
            if( $season === null ) {
                throw new \Exception('season not found');
            }
            if( $association === null ) {
                throw new \Exception('association not found');
            }
            $poolCollection = $this->poolCollectionRepos->findOneBy(['association' => $association]);
            if( $poolCollection === null ) {
                throw new \Exception('poolCollection not found');
            }
            $pool = $this->poolRepos->findOneBy(['collection' => $poolCollection]);
            if( $pool === null ) {
                throw new \Exception('pool not found');
            }
            if( $user === null ) {
                throw new \Exception('user not found');
            }
            $poolUser = $pool->getUser($user);
            if( $poolUser === null ) {
                throw new \Exception('poolUser not found');
            }
            $this->logFormation('    ', 'assemble', $poolUser->getAssembleFormation(), $logger );
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    private function logFormation(string $prefix, string $header, S11Formation|null $formation, LoggerInterface $logger ): void {
        $logger->info($prefix . $header);
        $prefix .= '    ';
        if( $formation === null) {
            $logger->info($prefix . 'no formation');
            return;
        }

    }


}