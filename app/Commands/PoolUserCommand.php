<?php

namespace App\Commands;

use App\Command;
use App\Commands\PoolUser\Action as PoolUserAction;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sports\Season;
use SuperElf\Achievement\BadgeCategory;
use SuperElf\Points;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Formation as S11Formation;
use SuperElf\Formation\Output as FormationOutput;

use SuperElf\Pool\Administrator as PoolAdministrator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * php bin/console.php app:pooluser --season=2022/2023 --pool='kamp duim' --user='boy' --loglevel=200
 */
final class PoolUserCommand extends Command
{
    private string $customName = 'pooluser';

//    private FormationValidator $formationValidator;
//    private bool $dryRun = false;

//    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PoolRepository $poolRepos;
    protected PoolAdministrator $poolAdministrator;
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

        /** @var PoolAdministrator $poolAdministrator */
        $poolAdministrator = $container->get(PoolAdministrator::class);
        $this->poolAdministrator = $poolAdministrator;

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

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('app:' . $this->customName)
            ->setDescription('action for the poolUser')
            ->setHelp('action for the poolUser');

        $actions = array_map(fn(PoolUserAction $action) => $action->value, PoolUserAction::cases());
        $this->addArgument('action', InputArgument::REQUIRED, join(',', $actions));

        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('user', null, InputOption::VALUE_REQUIRED, 'coen');
        // PoolUserAction::Show
        $this->addOption('pool', null, InputOption::VALUE_OPTIONAL, 'kamp duim');
        $this->addOption('badge', null, InputOption::VALUE_OPTIONAL, 'Card');
        // PoolUserAction::CopyFormationToOtherPool
        $this->addOption('from-pool', null, InputOption::VALUE_OPTIONAL, 'kamp duim');
        $this->addOption('to-pool', null, InputOption::VALUE_OPTIONAL, 'Arriva');

        // $this->addOption('dry-run', null, InputOption::VALUE_NONE);

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevelFromInput($input),
            $this->getStreamDefFromInput($input, $loggerName),
            $loggerName,
        );

        try {
            $action = $this->getAction($input);

            switch ($action) {
                case PoolUserAction::Show:
                    return $this->show($input);
                case PoolUserAction::CopyFormationToOtherPool:
                    return $this->copyFormationToOtherPool($input);
                default:
                    throw new \Exception('onbekende actie', E_ERROR);
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    protected function getAction(InputInterface $input): PoolUserAction
    {
        /** @var string $action */
        $action = $input->getArgument('action');
        return PoolUserAction::from($action);
    }


    private function show(InputInterface $input): int {
        $seasonName = $this->inputHelper->getStringFromInput($input, 'season');
        $poolName = $this->inputHelper->getStringFromInput($input, 'pool');
        $userName = $this->inputHelper->getStringFromInput($input, 'user');
        $badgeCategory = $this->inputHelper->getStringFromInput($input, 'badge', '');
        $badgeCategory = BadgeCategory::tryFrom($badgeCategory);

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
        $pool = null;
        $pools = $this->poolRepos->findBy(['collection' => $poolCollection]);
        foreach( $pools as $poolIt ) {
            if( $poolIt->getCompetitionConfig()->getSeason() === $season) {
                $pool = $poolIt;
                break;
            }
        }
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
        $s11Points = $pool->getCompetitionConfig()->getPoints();
        $totalPoints = $poolUser->getTotalPoints($s11Points, $badgeCategory);
        $this->logFormation('    ', 'assemble (tot: ' . $totalPoints . ')',
            $pool->getCompetitionConfig()->getPoints(),
            $poolUser->getAssembleFormation(),
            $badgeCategory,
            $this->getLogger() );
        $this->logFormation('    ', 'transfer',
            $pool->getCompetitionConfig()->getPoints(),
            $poolUser->getTransferFormation(),
            $badgeCategory,
            $this->getLogger() );
        return 0;
    }

    private function copyFormationToOtherPool(InputInterface $input): int {
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
        $this->poolAdministrator->copyPoolUserFormationToOtherPool($user, $fromPool, $toPool );
        $this->logFormation(
            '    ',
            'copy for user "' . $user->getName() . '" and season "' . $season->getName() . '" from pool "'.$fromPool->getName().'" to pool "'.$toPool->getName().'"',
            $fromPool->getCompetitionConfig()->getPoints(),
            $fromPoolUser->getAssembleFormation(),
            null,
            $this->getLogger() );
        $this->getLogger()->info('COPYING DONE!');
        return 0;
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

    private function findPoolByNameAndSeason(Season $season, string $poolName): Pool {
        $pools = $this->poolRepos->findByFilter($poolName, $season->getStartDateTime(), $season->getEndDateTime());
        $pool = reset($pools);
        if ( $pool === false ) {
            throw new \Exception('pool "' . $poolName .'" not found');
        }
        return $pool;
    }
}