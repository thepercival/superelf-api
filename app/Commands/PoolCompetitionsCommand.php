<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\QueueService;
use Psr\Container\ContainerInterface;
use Sports\Season;
use Sports\Structure\Repository as StructureRepository;
use SportsImport\Importer;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Competitor\Repository as CompetitorRepository;
use SuperElf\Formation\Editor as FormationEditor;
use SuperElf\League;
use SuperElf\CompetitionConfig as CompetitionConfigBase;
use SuperElf\League as S11League;
use SuperElf\Pool;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PoolCompetitionsCommand extends Command
{
    protected PoolRepository $poolRepos;
    protected PoolAdministrator $poolAdmin;
    protected CompetitorRepository $competitorRepos;
    protected StructureRepository $structureRepos;
    protected PoolUserRepository $poolUserRepos;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected FormationEditor $formationEditor;
    protected Importer $importer;

    public function __construct(ContainerInterface $container)
    {
        /** @var PoolRepository $poolRepos */
        $poolRepos = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepos;

        /** @var PoolUserRepository $poolUserRepos */
        $poolUserRepos = $container->get(PoolUserRepository::class);
        $this->poolUserRepos = $poolUserRepos;

        /** @var PoolAdministrator $poolAdmin */
        $poolAdmin = $container->get(PoolAdministrator::class);
        $this->poolAdmin = $poolAdmin;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var CompetitorRepository $competitorRepos */
        $competitorRepos = $container->get(CompetitorRepository::class);
        $this->competitorRepos = $competitorRepos;

        /** @var StructureRepository $structureRepos */
        $structureRepos = $container->get(StructureRepository::class);
        $this->structureRepos = $structureRepos;

        /** @var Importer $importer */
        $importer = $container->get(Importer::class);
        $this->importer = $importer;

        parent::__construct($container);
        $this->importer->setGameEventSender(new QueueService($this->config->getArray('queue')));
        $this->formationEditor = new FormationEditor($this->config, false);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:create-pool-competitions')
            // the short description shown while running "php bin/console list"
            ->setDescription('creates the superelf-pool-competitions')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('creates the superelf-pool-competitions');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2024/2025');
        $this->addOption('s11league', null, InputOption::VALUE_REQUIRED, 'SuperCup');
        $this->addOption('replace', null, InputOption::VALUE_NONE);

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-create-pool-competitions');

        // loop door alle pools van een bepaald seizoen
        // per pool de competities verwijderen en weer opnieuw aanmaken
        /** @var bool|null $replace */
        $replace = $input->getOption('replace');
        $replace = is_bool($replace) ? $replace : false;


        try {
            $compConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

            $filterS11League = $this->getS11LeagueFromInput($input);

            $pools = $this->poolRepos->findBy(['competitionConfig' => $compConfig]);
            foreach ($pools as $pool) {
                if( $pool->getName() === S11League::WorldCup->name) {
                    continue;
                }

                $this->getLogger()->info(
                    'removing invalid poolUsers for pool "' . $pool->getName() . '"(' . (string)$pool->getId() . ')'
                );
                $this->removeInvalidPoolUsers($pool);

                $this->getLogger()->info(
                    'creating competitions for pool "' . $pool->getName() . '"(' . (string)$pool->getId() . ')'
                );
                if ($replace) {
                    $this->poolAdmin->replaceCompetitionsCompetitorsStructureAndGames($pool, $filterS11League);
                } else {
                    $this->poolAdmin->createCompetitionsCompetitorsStructureAndGames($pool, $filterS11League);
                }
            }
            if( $filterS11League === null || $filterS11League === S11League::WorldCup ) {
                $worldCupPool = $this->createWorldCupPool($compConfig);
                if ($replace) {
                    $this->replacePoolUsersCompetitionsCompetitorsStructureAndGames($worldCupPool);
                } else {
                    $this->createPoolUsersCompetitionsCompetitorsStructureAndGames($worldCupPool);
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    /**
     * @param Pool $worldCupPool
     * @return void
     * @throws \Exception
     */
    private  function replacePoolUsersCompetitionsCompetitorsStructureAndGames(Pool $worldCupPool): void
    {
        $this->poolAdmin->checkOnStartedGames($worldCupPool, S11League::WorldCup);
        $this->poolAdmin->removeCompetitionsCompetitorsStructureAndGames($worldCupPool, S11League::WorldCup);

        $this->poolAdmin->replaceWorldCupPoolUsers($worldCupPool);
        $this->poolAdmin->createCompetitionsCompetitorsStructureAndGames($worldCupPool, S11League::WorldCup);

    }

    /**
     * @param Pool $worldCupPool
     * @return void
     * @throws \Exception
     */
    private  function createPoolUsersCompetitionsCompetitorsStructureAndGames(Pool $worldCupPool): void
    {
        $this->poolAdmin->checkOnStartedGames($worldCupPool, S11League::WorldCup);
        $this->poolAdmin->replaceWorldCupPoolUsers($worldCupPool);
        $this->poolAdmin->createCompetitionsCompetitorsStructureAndGames($worldCupPool, S11League::WorldCup);

    }

    private function createWorldCupPool(CompetitionConfigBase $compConfig): Pool {
        $worldCupPool = $this->poolRepos->findWorldCup($compConfig);
        if( $worldCupPool !== null ) {
            return $worldCupPool;
        }
        return $this->poolAdmin->createPool($compConfig, League::WorldCup->name, null, true);
    }

    private function removeInvalidPoolUsers(Pool $pool): void {
        $this->getLogger()->info(
            'removing invalid poolUsers for pool "' . $pool->getName() . '"(' . (string)$pool->getId() . ')'
        );

        $poolUsers = $pool->getUsers()->toArray();
        foreach( $poolUsers as $poolUser ) {
            if( !$poolUser->canCompete()) {
                $pool->getUsers()->removeElement($poolUser);
                $this->poolUserRepos->remove($poolUser);
                $this->getLogger()->info(
                    '   removing invalid poolUser "' . $poolUser->getUser()->getName() . '"'
                );
            }
        }

    }

    public function getS11LeagueFromInput(InputInterface $input): S11League|null
    {
        $optionValue = $input->getOption('s11league');
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            return null;
        }
        return S11League::from($optionValue);
    }


//    /**
//     * @param InputInterface $input
//     * @param Season $season
//     * @return list<Pool>
//     */
//    protected function getPools(InputInterface $input, Season $season): array
//    {
//        if ($input->getArgument('poolId') !== null) {
//            $poolId = (string)$input->getArgument('poolId');
//            $pools = [];
//            $pool = $this->poolRepos->find((int)$poolId);
//            if ($pool !== null) {
//                $pools[] = $pool;
//            }
//            return $pools;
//        }
//        return $this->poolRepos->findByFilter(null, $season->getStartDateTime(), $season->getEndDateTime());
//    }
//
//    protected function importAssociations(Competitions $externalSourceCompetitions, Sport $sport): void
//    {
//        $this->importer->importAssociations(
//            $externalSourceCompetitions, $externalSourceCompetitions->getExternalSource(), $sport);
//    }
//
//    protected function importSeasons(Competitions $externalSourceCompetitions, ExternalSource $externalSource): void
//    {
//        $this->importer->importSeasons($externalSourceCompetitions, $externalSource);
//    }
//
//    protected function importLeagues(
//        Competitions $externalSourceCompetitions, ExternalSource $externalSource,
//        Association $association): void
//    {
//        $this->importer->importLeagues($externalSourceCompetitions, $externalSource, $association);
//    }
//
//    protected function importCompetition(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ): void {
//        $this->importer->importCompetition($externalSourceImpl, $sport, $association, $league, $season);
//    }
//
//    protected function importTeams(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ) {
//        $this->importer->importTeams($externalSourceImpl, $sport, $association, $league, $season);
//    }
//
//    protected function importTeamCompetitors(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ) {
//        $this->importer->importTeamCompetitors($externalSourceImpl, $sport, $association, $league, $season);
//    }
//
//    protected function importStructure(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ) {
//        $this->importer->importStructure($externalSourceImpl, $sport, $association, $league, $season);
//    }
//
//    protected function importSchedule(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ) {
//        $this->importer->importSchedule($externalSourceImpl, $sport, $association, $league, $season);
//    }
//
//    protected function importGameDetails(
//        Implementation $externalSourceImpl,
//        Sport $sport,
//        Association $association,
//        League $league,
//        Season $season
//    ) {
//        // bepaal de period waarin gezocht moet worden
//        // voor de cronjob is 24, 3 en 2 uur na de start van de wedstrijd
//
//
//        $period = new Period(
//            new \DateTimeImmutable('2020-10-18 12:29'),
//            new \DateTimeImmutable('2020-10-18 12:31')
//        ); // klaiber
//        // HIER VERDER
//        /*$period = new Period(
//            new \DateTimeImmutable('2020-09-01 08:00'),
//            new \DateTimeImmutable('2020-09-21 08:00') );
//        $period = new Period(
//            new \DateTimeImmutable('2020-09-21 08:00'),
//            new \DateTimeImmutable('2020-10-16 08:00') );
//        $period = new Period(
//            new \DateTimeImmutable('2020-10-16 08:00'),
//            new \DateTimeImmutable('2020-10-19 08:00') );*/
//        $period = new Period(
//            new \DateTimeImmutable('2020-10-19 08:00'),
//            new \DateTimeImmutable('2020-12-11 08:00')
//        );
//        $this->importer->importGameDetails($externalSourceImpl, $sport, $association, $league, $season, $period);
//    }
//
//    protected function importImages(Implementation $externalSourceImpl, League $league, Season $season)
//    {
//        $localPath = $this->config->getString('www.apiurl-localpath');
//        $localPath .= $this->config->getString('images.playersSuffix');
//        $publicPath = $this->config->getString('www.apiurl');
//        $publicPath .= $this->config->getString('images.playersSuffix');
//        $maxWidth = 150;
//        $this->importer->importPersonImages(
//            $externalSourceImpl,
//            $league,
//            $season,
//            $localPath,
//            $publicPath,
//            $maxWidth
//        );
//
//        $localPath = $this->config->getString('www.apiurl-localpath');
//        $localPath .= $this->config->getString('images.teamsSuffix');
//        $publicPath = $this->config->getString('www.apiurl');
//        $publicPath .= $this->config->getString('images.teamsSuffix');
//        $maxWidth = 150;
//        $this->importer->importTeamImages(
//            $externalSourceImpl,
//            $league,
//            $season,
//            $localPath,
//            $publicPath,
//            $maxWidth
//        );
//    }
}
