<?php

namespace App\Commands;

use App\Command;
use App\Commands\Person\Action as PersonAction;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sports\Season;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Formation as S11Formation;
use SuperElf\Formation\Output as FormationOutput;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:poolusercopy --season="2024/2025" --user="joris" --from-pool="kamp duim" --to-pool="Arriva" --loglevel=200
 */
class PoolUserCopyCommand extends Command
{
    private string $customName = 'poolusercopy';

//    private FormationValidator $formationValidator;
//    private bool $dryRun = false;
//
//    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PoolRepository $poolRepos;
    protected PoolAdministrator $poolAdmin;
    protected PoolUserRepository $poolUserRepos;
    protected PoolCollectionRepository $poolCollectionRepos;
    protected UserRepository $userRepos;

//    protected S11FormationRepository $s11FormationRepos;
//    protected S11PlayerSyncer $s11PlayerSyncer;

    public function __construct(ContainerInterface $container)
    {
        /** @var PoolUserRepository $poolUserRepos */
        $poolUserRepos = $container->get(PoolUserRepository::class);
        $this->poolUserRepos = $poolUserRepos;
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

        /** @var PoolAdministrator $poolAdmin */
        $poolAdmin = $container->get(PoolAdministrator::class);
        $this->poolAdmin = $poolAdmin;
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
        $this->addOption('user', null, InputOption::VALUE_REQUIRED, 'coen');
        $this->addOption('from-pool', null, InputOption::VALUE_REQUIRED, 'kamp duim');
        $this->addOption('to-pool', null, InputOption::VALUE_REQUIRED, 'Arriva');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevelFromInput($input),
            $this->getStreamDefFromInput($input, $loggerName),
            $loggerName,
        );

        try {
            $seasonName = $this->inputHelper->getStringFromInput($input, 'season');
            $userName = $this->inputHelper->getStringFromInput($input, 'user');
            $fromPoolName = $this->inputHelper->getStringFromInput($input, 'from-pool');
            $toPoolName = $this->inputHelper->getStringFromInput($input, 'to-pool');

            $season = $this->seasonRepos->findOneBy(['name' => $seasonName]);
            if( $season === null ) {
                throw new \Exception('season not found');
            }
            $fromPool = $this->findPoolByNameAndSeason($season, $fromPoolName);
            $toPool = $this->findPoolByNameAndSeason($season, $toPoolName);
            $user = $this->userRepos->findOneBy(['name' => $userName]);
            if( $user === null ) {
                throw new \Exception('user not found');
            }
            $fromPoolUser = $fromPool->getUser($user);
            if( $fromPoolUser === null ) {
                throw new \Exception('from-poolUser not found');
            }
            $toPoolUser = $toPool->getUser($user);
            if( $toPoolUser === null ) {
                throw new \Exception('to-poolUser not found');
            }
            // remove to-pool-formation
            $this->poolAdmin->copyPoolUserFormationToOtherPool($user, $fromPool, $toPool );
            $this->logFormation(
                '    ',
                'copy for user "' . $user->getName() . '" from pool "'.$fromPool->getName().'" to pool "'.$toPool->getName().'"',
                $fromPool->getCompetitionConfig()->getPoints(),
                $fromPoolUser->getAssembleFormation(),
                null,
                $logger );
//            $this->logFormation(
//                '    ',
//                'transfer',
//                $pool->getCompetitionConfig()->getPoints(),
//                $poolUser->getTransferFormation(),
//                $badgeCategory,
//                $logger );
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    private function findPoolByNameAndSeason(Season $season, string $poolName): Pool {
        $pools = $this->poolRepos->findByFilter($poolName, $season->getStartDateTime(), $season->getEndDateTime());
        $pool = reset($pools);
        if ( $pool === false ) {
            throw new \Exception('pool "' . $poolName .'" not found');
        }
        return $pool;
    }

    private function logFormation(
        string $prefix,
        string $header,
        Points $points,
        S11Formation|null $formation,
        BadgeCategory|null $badgeCategory,
        LoggerInterface $logger ): void {
        $logger->info($prefix . $header);
        $prefix .= '    ';
        $formationOutput = new FormationOutput($logger);
        if( $formation === null) {
            $logger->info($prefix . 'no formation');
            return;
        }
        $formationOutput->output($points, $formation, $badgeCategory);
    }
}