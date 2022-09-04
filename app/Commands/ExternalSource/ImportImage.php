<?php

declare(strict_types=1);

namespace App\Commands\ExternalSource;

use App\Commands\ExternalSource as ExternalSourceCommand;
use App\QueueService;
use Psr\Container\ContainerInterface;
use Sports\League;
use Sports\Person;
use Sports\Season;
use Sports\Team;
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\GamesAndPlayers;
use SportsImport\Importer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportImage extends ExternalSourceCommand
{
    protected Importer $importer;

    public function __construct(ContainerInterface $container)
    {
        /** @var Importer $importer */
        $importer = $container->get(Importer::class);
        $this->importer = $importer;
        parent::__construct($container);
        $this->importer->setGameEventSender(new QueueService($this->config->getArray('queue')));
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:import:image')
            // the short description shown while running "php bin/console list"
            ->setDescription('imports the images')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('import the images');

        $this->addOption('teamId', null, InputOption::VALUE_OPTIONAL, 'teamId');
        $this->addOption('personId', null, InputOption::VALUE_OPTIONAL, 'personId');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-import-image');
        $externalSourceName = (string)$input->getArgument('externalSource');
        $externalSourceImpl = $this->externalSourceFactory->createByName($externalSourceName);
        if ($externalSourceImpl === null) {
            $message = "voor '" . $externalSourceName . "' kan er geen externe bron worden gevonden";
            $this->getLogger()->error($message);
            return -1;
        }

        $entity = $this->getEntityFromInput($input);

        try {
            if ($externalSourceImpl instanceof Competitions &&
                $externalSourceImpl instanceof CompetitionStructure &&
                $externalSourceImpl instanceof GamesAndPlayers) {
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                if ($entity === Entity::TEAMS) {
                    $inputTeam = $this->inputHelper->getTeamFromInput($input);
                    $this->importTeamImages(
                        $externalSourceImpl,
                        $externalSourceImpl->getExternalSource(),
                        $league,
                        $season,
                        $inputTeam
                    );
                } else {
                    $inputPerson = $this->inputHelper->getPersonFromInput($input);
                    $this->importPlayerImages(
                        $externalSourceImpl,
                        $externalSourceImpl->getExternalSource(),
                        $league,
                        $season,
                        $inputPerson
                    );
                }
                return 0;
            }
            throw new \Exception('objectType "' . $entity . '" kan niet worden opgehaald uit externe bronnen', E_ERROR);
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function importTeamImages(
        CompetitionStructure $externalSourceCompetitionStructure,
        ExternalSource $externalSource,
        League $league,
        Season $season,
        Team|null $team = null
    ): void {
        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.teamsSuffix');
        $maxWidth = 150;
        $this->importer->importTeamImages(
            $externalSourceCompetitionStructure,
            $externalSource,
            $league,
            $season,
            $localPath,
            $maxWidth,
            $team
        );
    }

    protected function importPlayerImages(
        GamesAndPlayers $externalSourceGamesAndPlayers,
        ExternalSource $externalSource,
        League $league,
        Season $season,
        Person|null $person = null
    ): void {
        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.playersSuffix');
        $this->importer->importPlayerImages(
            $externalSourceGamesAndPlayers,
            $externalSource,
            $league,
            $season,
            $localPath,
            $person
        );
    }
}
