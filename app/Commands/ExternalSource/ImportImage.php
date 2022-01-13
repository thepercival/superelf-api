<?php

declare(strict_types=1);

namespace App\Commands\ExternalSource;

use App\Commands\ExternalSource as ExternalSourceCommand;
use App\QueueService;
use Psr\Container\ContainerInterface;
use Sports\League;
use Sports\Season;
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Competitions;
use SportsImport\ExternalSource\CompetitionStructure;
use SportsImport\ExternalSource\GamesAndPlayers;
use SportsImport\Importer;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->importer->setEventSender(new QueueService($this->config->getArray('queue')));
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
                $league = $this->getLeagueFromInput($input);
                $season = $this->getSeasonFromInput($input);
                if ($entity === Entity::TEAMS) {
                    $this->importTeamImages(
                        $externalSourceImpl,
                        $externalSourceImpl->getExternalSource(),
                        $league,
                        $season
                    );
                } else {
                    $this->importPlayerImages(
                        $externalSourceImpl,
                        $externalSourceImpl->getExternalSource(),
                        $league,
                        $season
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
        Season $season
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
            $maxWidth
        );
    }

    protected function importPlayerImages(
        GamesAndPlayers $externalSourceGamesAndPlayers,
        ExternalSource $externalSource,
        League $league,
        Season $season
    ): void {
        $localPath = $this->config->getString('www.apiurl-localpath');
        $localPath .= $this->config->getString('images.playersSuffix');
        $this->importer->importPlayerImages(
            $externalSourceGamesAndPlayers,
            $externalSource,
            $league,
            $season,
            $localPath
        );
    }
}
