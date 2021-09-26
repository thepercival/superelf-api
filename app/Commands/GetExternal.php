<?php

declare(strict_types=1);

namespace App\Commands;

use Sports\Association;
use Sports\Season;
use Sports\Sport;
use SportsHelpers\Range;
use SportsImport\ExternalSource\ApiHelper;
use SportsImport\ExternalSource\CacheInfo;
use Sports\Output\ConsoleTable;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use Psr\Container\ContainerInterface;
use App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource;
use SportsImport\Service as ImportService;

class GetExternal extends Command
{
    protected ExternalSourceFactory $externalSourceFactory;
    protected ImportService $importService;
    protected CompetitionRepository $competitionRepos;
    protected CompetitionAttacherRepository $competitionAttacherRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->importService = $container->get(ImportService::class);
        $this->competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionAttacherRepos = $container->get(CompetitionAttacherRepository::class);
        parent::__construct($container);
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:getexternal')
            // the short description shown while running "php bin/console list"
            ->setDescription('gets the external objects')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('import the objects');

        $this->addArgument('externalSource', InputArgument::REQUIRED, 'for example sofascore');
        $this->addArgument('objectType', InputArgument::REQUIRED, 'for example associations or competitions');

        parent::configure();
    }

    protected function init(InputInterface $input, string $name)
    {
        $this->initLogger($input, $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->init($input, 'cron-getexternal');

        $externalSourceName = $input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if ($externalSourceImpl === null) {
            if( $this->logger !== null) {
                $message = "voor \"" . $externalSourceName . "\" kan er geen externe bron worden gevonden";
                $this->logger->error($message);
            }
            return -1;
        }

        $objectType = $input->getArgument('objectType');

        try {
            if ($objectType === "sports") {
                $this->getSports($externalSourceImpl);
            } elseif ($objectType === "seasons") {
                $this->getSeasons($externalSourceImpl);
            } else {
                $sport = $this->getSportFromInput($input);
                if ($objectType === "associations") {
                    $this->getAssociations($externalSourceImpl, $sport);
                } else {
                    $association = $this->getAssociationFromInput($input);
                    if ($objectType === "leagues") {
                        $this->getLeagues($externalSourceImpl, $sport, $association);
                    } else {
                        $league = $this->getLeagueFromInput($input);
                        if ($objectType === "competitions") {
                            $this->getCompetitions($externalSourceImpl, $sport, $association, $league);
                        } else {
                            $season = $this->getSeasonFromInput($input);
                            if ($objectType === "teams") {
                                $this->getTeams($externalSourceImpl, $sport, $association, $league, $season);
                            } elseif ($objectType === "teamcompetitors") {
                                $this->getTeamCompetitors($externalSourceImpl, $sport, $association, $league, $season);
                            } elseif ($objectType === "structure") {
                                $this->getStructure($externalSourceImpl, $sport, $association, $league, $season);
                            } elseif ($objectType === "games") {
                                $batchNrRange = $this->getBatchNrRangeFromInput($input);
                                $this->getGames(
                                    $externalSourceImpl,
                                    $sport,
                                    $association,
                                    $league,
                                    $season,
                                    $batchNrRange
                                );
                            } elseif ($objectType === "game") {
                                $this->getGame(
                                    $externalSourceImpl,
                                    $sport,
                                    $association,
                                    $league,
                                    $season,
                                    $this->getIdFromInput($input)
                                );
                            } else {
                                if( $this->logger !== null) {
                                    $message = "objectType \"" . $objectType . "\" kan niet worden opgehaald uit externe bronnen";
                                    $this->logger->error($message);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            if( $this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }

//        if ($input->getOption("structures")) {
//            $this->importStructures(SofaScore::NAME);
//        }
//        if ($input->getOption("games")) {
//            $this->importGames(SofaScore::NAME);
//        }

        return 0;
    }

    protected function showMetadata(ExternalSource\Implementation $externalSourceImpl, int $dataType)
    {
        if ($externalSourceImpl instanceof CacheInfo) {
            $this->logger->info($externalSourceImpl->getCacheInfo($dataType));
        }
        if ($externalSourceImpl instanceof ApiHelper) {
            $this->logger->info("endpoint: " . $externalSourceImpl->getEndPoint($dataType));
        }
    }

    protected function getSports(ExternalSource\Implementation $externalSourceImpl)
    {
        if (!($externalSourceImpl instanceof ExternalSource\Sport)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen sporten opvragen", E_ERROR
            );
        }
        $table = new ConsoleTable\Sports();
        $table->display($externalSourceImpl->getSports());
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_SPORTS);
    }

    protected function getAssociations(ExternalSource\Implementation $externalSourceImpl, Sport $sport)
    {
        if (!($externalSourceImpl instanceof ExternalSource\Association)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen bonden opvragen", E_ERROR
            );
        }
        $table = new ConsoleTable\Associations();
        $table->display($externalSourceImpl->getAssociations($sport));
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_ASSOCIATIONS);
    }

    protected function getSeasons(ExternalSource\Implementation $externalSourceImpl)
    {
        if (!($externalSourceImpl instanceof ExternalSource\Season)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen seizoenen opvragen", E_ERROR
            );
        }
        $table = new ConsoleTable\Seasons();
        $table->display($externalSourceImpl->getSeasons());

        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_SEASONS);
    }

    protected function getLeagues(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\League)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen competities opvragen", E_ERROR
            );
        }
        $externalAssociation = $this->importService->getExternalAssociation($externalSourceImpl, $sport, $association);
        $table = new ConsoleTable\Leagues();
        $table->display($externalSourceImpl->getLeagues($externalAssociation));
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_LEAGUES);
    }

    protected function getCompetitions(ExternalSource\Implementation $externalSourceImpl, Sport $sport, Association  $association, League $league)
    {
        if (!($externalSourceImpl instanceof ExternalSource\Competition)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen competities opvragen", E_ERROR
            );
        }
        $externalLeague = $this->importService->getExternalLeague(
            $externalSourceImpl, $sport, $association, $league
        );
        $table = new ConsoleTable\Competitions();
        $table->display($externalSourceImpl->getCompetitions($sport, $externalLeague));
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_COMPETITIONS);
    }

    protected function getTeams(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association,
        League $league,
        Season $season
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\Team)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen teams opvragen", E_ERROR
            );
        }
        $competition = $this->importService->getExternalCompetition(
            $externalSourceImpl,
            $sport,
            $association,
            $league,
            $season
        );
        $table = new ConsoleTable\Teams();
        $table->display($externalSourceImpl->getTeams($competition));
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_TEAMS);
    }

    protected function getTeamCompetitors(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association,
        League $league,
        Season $season
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\Competitor\Team)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen teamcompetitors opvragen", E_ERROR
            );
        }
        $competition = $this->importService->getExternalCompetition(
            $externalSourceImpl,
            $sport,
            $association,
            $league,
            $season
        );
        $table = new ConsoleTable\TeamCompetitors();
        $table->display($externalSourceImpl->getTeamCompetitors($competition));
        $this->showMetadata($externalSourceImpl, ExternalSource::DATA_TEAMCOMPETITORS);
    }

    protected function getStructure(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association,
        League $league,
        Season $season
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\Competitor\Team)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen teamcompetitors opvragen", E_ERROR
            );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Structure)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen structuur opvragen", E_ERROR
            );
        }
        $competition = $this->importService->getExternalCompetition(
            $externalSourceImpl,
            $sport,
            $association,
            $league,
            $season
        );

        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
        $table = new ConsoleTable\Structure();
        $table->display($competition, $externalSourceImpl->getStructure($competition), $teamCompetitors);
    }

    protected function getGames(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association,
        League $league,
        Season $season,
        Range $batchNrRange
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\Competition)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen competitieseizoenen opvragen", E_ERROR
            );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Structure)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen structuur opvragen", E_ERROR
            );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Competitor\Team)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen deelnemers opvragen", E_ERROR
            );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Game)) {
            throw new \Exception(
                "de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen wedstrijden opvragen", E_ERROR
            );
        }
        $competition = $this->importService->getExternalCompetition(
            $externalSourceImpl,
            $sport,
            $association,
            $league,
            $season
        );

        $batchNrs = $externalSourceImpl->getBatchNrs($competition);
        $games = [];
        for ($batchNr = $batchNrRange->min; $batchNr <= $batchNrRange->max; $batchNr++) {
            if (count(
                    array_filter(
                        $batchNrs,
                        function (int $batchNrIt) use ($batchNr): bool {
                            return $batchNrIt === $batchNr;
                        }
                    )
                ) === 0) {
                $this->logger->info("batchnr " . $batchNr . " komt niet voor in de externe bron");
            }
            $games = array_merge($games, $externalSourceImpl->getGames($competition, $batchNr));
        }
        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
        $table = new ConsoleTable\Games();
        $table->display($competition, $games, $teamCompetitors);
    }

    /**
     * @param ExternalSource\Implementation $externalSourceImpl
     * @param League $league
     * @param Season $season
     * @param string|int $gameId
     * @throws \Exception
     */
    protected function getGame(
        ExternalSource\Implementation $externalSourceImpl,
        Sport $sport,
        Association $association,
        League $league,
        Season $season,
        $gameId
    ) {
        if (!($externalSourceImpl instanceof ExternalSource\Competition)) {
            throw new \Exception("de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen competitieseizoenen opvragen", E_ERROR );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Structure)) {
            throw new \Exception("de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen structuur opvragen", E_ERROR );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Competitor\Team)) {
            throw new \Exception("de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen deelnemers opvragen", E_ERROR );
        }
        if (!($externalSourceImpl instanceof ExternalSource\Game)) {
            throw new \Exception("de externe bron \"" . $externalSourceImpl->getExternalSource()->getName(
                ) . "\" kan geen wedstrijden opvragen", E_ERROR );
        }

        $competition = $this->importService->getExternalCompetition(
            $externalSourceImpl,
            $sport,
            $association,
            $league,
            $season
        );

        $game = $externalSourceImpl->getGame($competition, $gameId);

        $teamCompetitors = $externalSourceImpl->getTeamCompetitors($competition);
        $table = new ConsoleTable\Game();
        $table->display($competition, $game, $teamCompetitors);
    }

}
