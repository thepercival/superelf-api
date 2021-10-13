<?php

declare(strict_types=1);

namespace App\Commands;

use App\QueueService;
use League\Period\Period;
use Psr\Container\ContainerInterface;
use App\Command;

use Sports\Association;
use Sports\League;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\Administrator as PoolAdministrator;
use Sports\Season;
use SuperElf\CompetitionsCreator;
use Sports\Sport;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\Implementation;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource;
use SportsImport\Importer;

class CreateCompetitions extends Command
{
    protected PoolRepository $poolRepos;
    protected PoolAdministrator $poolAdmin;
    protected CompetitionsCreator $competitionsCreator;
    protected Importer $importer;

    public function __construct(ContainerInterface $container)
    {
        /** @var PoolRepository poolRepos */
        $this->poolRepos = $container->get(PoolRepository::class);
        /** @var PoolAdministrator poolAdmin */
        $this->poolAdmin = $container->get(PoolAdministrator::class);
        /** @var Importer importer */
        $this->importer = $container->get(Importer::class);
        parent::__construct($container, 'command-create-competitions');
        $this->importer->setEventSender(new QueueService($this->config->getArray('queue')));
        $this->competitionsCreator = new CompetitionsCreator();
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:create-competitions')
            // the short description shown while running "php bin/console list"
            ->setDescription('creates the superelf-competitions')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('creates the superelf-competitions');

        $this->addArgument('poolId', InputArgument::REQUIRED, 'create for one pool');
        // $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or competitions');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLoggerFromInput($input);
        // loop door alle pools van een bepaald seizoen
        // per pool de competities verwijderen en weer opnieuw aanmaken

        try {
            $season = null;
            if ($input->getArgument('name') !== null) {
                $season = $this->seasonRepos->findOneBy(["name" => $input->getArgument('name') ]);
            }
            if ($season === null) {
                throw new \Exception("het seizoen kon niet gevonden worden");
            }
            $pools = $this->getPools($input, $season);
            foreach ($pools as $pool) {
                $this->logger->info("create competitions for pool " . $pool->getName() . "(".(string)$pool->getId().")");
                $this->competitionsCreator->recreateDetails($pool);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param Season $season
     * @return list<Pool>
     */
    protected function getPools(InputInterface $input, Season $season): array
    {
        if ($input->getArgument('poolId') !== null) {
            $poolId = (string)$input->getArgument('poolId');
            $pools = [];
            $pool = $this->poolRepos->find((int)$poolId);
            if ($pool !== null) {
                $pools[] = $pool;
            }
            return $pools;
        }
        return $this->poolRepos->findByFilter(null, $season->getStartDateTime(), $season->getEndDateTime());
    }
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
//        $localPath .= $this->config->getString('images.personsSuffix');
//        $publicPath = $this->config->getString('www.apiurl');
//        $publicPath .= $this->config->getString('images.personsSuffix');
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
