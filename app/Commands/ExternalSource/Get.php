<?php

declare(strict_types=1);

namespace App\Commands\ExternalSource;

use App\Commands\ExternalSource as ExternalSourceCommand;
use App\SportsImportHelpers\ExternalSourceGetter;
use Psr\Container\ContainerInterface;
use Sports\Association;
use Sports\Competitor\StartLocationMap;
use Sports\League;
use Sports\Output\ConsoleTable;
use Sports\Season;
use Sports\Sport;
use Sports\Structure\NameService as StructureNameService;
use SportsHelpers\SportRange;
use SportsImport\Entity;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\ExternalSourceGamesAndPlayersInterface;
use SportsImport\ExternalSource\ExternSourceCompetitionsInterface;
use SportsImport\ExternalSource\ExternSourceCompetitionStructureInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Get extends ExternalSourceCommand
{
    protected ExternalSourceGetter $getter;

    public function __construct(ContainerInterface $container)
    {
        /** @var ExternalSourceGetter $getter */
        $getter = $container->get(ExternalSourceGetter::class);
        $this->getter = $getter;

        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:get-external')
            // the short description shown while running "php bin/console list"
            ->setDescription('gets the external objects')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('import the objects');

        $this->addOption('gameRoundRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'external-game-id');
        $this->addOption('internal-id', null, InputOption::VALUE_OPTIONAL, 'internal-game-id');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-get-external');

        $externalSourceName = (string)$input->getArgument('externalSource');

        $externalSourceImpl = $this->getExternalSourceImplFromInput($input);
        if ($externalSourceImpl === null) {
            $message = 'voor "' . $externalSourceName . '" kan er geen externe bron worden gevonden';
            $this->getLogger()->error($message);
            return -1;
        }
        $externalSource = $externalSourceImpl->getExternalSource();

        $entity = $this->getEntityFromInput($input);

        try {
            if ($externalSourceImpl instanceof ExternSourceCompetitionsInterface) {
                switch ($entity) {
                    case Entity::SPORTS:
                        $this->showSports($externalSourceImpl);
                        return 0;
                    case Entity::SEASONS:
                        $this->showSeasons($externalSourceImpl);
                        return 0;
                    case Entity::ASSOCIATIONS:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $this->showAssociations($externalSourceImpl, $sport);
                        return 0;
                    case Entity::LEAGUES:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $association = $this->inputHelper->getAssociationFromInput($input);
                        $this->showLeagues($externalSourceImpl, $externalSource, $sport, $association);
                        return 0;
                    case Entity::COMPETITIONS:
                        $sport = $this->inputHelper->getSportFromInput($input);
                        $league = $this->inputHelper->getLeagueFromInput($input);
                        $this->showCompetitions($externalSourceImpl, $externalSource, $sport, $league);
                        return 0;
                }
            }
            if ($externalSourceImpl instanceof ExternSourceCompetitionsInterface &&
                $externalSourceImpl instanceof ExternSourceCompetitionStructureInterface) {
                $sport = $this->inputHelper->getSportFromInput($input);
//                $association = $this->getAssociationFromInput($input);
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::TEAMS:
                        $this->showTeams(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSource,
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                    case Entity::TEAMCOMPETITORS:
                        $this->showTeamCompetitors(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSource,
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                    case Entity::STRUCTURE:
                        $this->showStructure(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSource,
                            $sport,
                            $league,
                            $season
                        );
                        return 0;
                }
            }
            if ($externalSourceImpl instanceof ExternSourceCompetitionsInterface &&
                $externalSourceImpl instanceof ExternSourceCompetitionStructureInterface &&
                $externalSourceImpl instanceof ExternalSourceGamesAndPlayersInterface) {
                $sport = $this->inputHelper->getSportFromInput($input);
                $league = $this->inputHelper->getLeagueFromInput($input);
                $season = $this->inputHelper->getSeasonFromInput($input);
                switch ($entity) {
                    case Entity::GAMES_BASICS:
                        $gameRoundRange = $this->inputHelper->getGameRoundNrRangeFromInput($input);
                        $this->showAgainstGamesBasics(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSource,
                            $sport,
                            $league,
                            $season,
                            $gameRoundRange !== null ? $gameRoundRange : new SportRange(1, 1)
                        );
                        return 0;
                    case Entity::GAME:
                        $externalGameId = $this->getExternalGameId($input, $externalSource);

                        $this->showAgainstGame(
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSourceImpl,
                            $externalSource,
                            $sport,
                            $league,
                            $season,
                            $externalGameId,
                            $this->getGameCacheOptionFromInput($input)
                        );
                        return 0;
                }
            }
            throw new \Exception('objectType "' . $entity . '" kan niet worden opgehaald uit externe bronnen', E_ERROR);
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function showSports(ExternSourceCompetitionsInterface $externalSourceCompetitions): void
    {
        $table = new ConsoleTable\Sports();
        $table->display(array_values($externalSourceCompetitions->getSports()));
    }

    protected function showAssociations(ExternSourceCompetitionsInterface $externalSourceCompetitions, Sport $sport): void
    {
        $table = new ConsoleTable\Associations();
        $table->display(array_values($externalSourceCompetitions->getAssociations($sport)));
    }

    protected function showSeasons(ExternSourceCompetitionsInterface $externalSourceCompetitions): void
    {
        $table = new ConsoleTable\Seasons();
        $table->display(array_values($externalSourceCompetitions->getSeasons()));
    }

    protected function showLeagues(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource $externalSource,
        Sport $sport,
        Association $association
    ): void {
        $externalAssociation = $this->getter->getAssociation($externalSourceCompetitions, $externalSource, $sport, $association);
        $table = new ConsoleTable\Leagues();
        $leagues = array_values($externalSourceCompetitions->getLeagues($externalAssociation));
        $table->display($leagues);
    }

    protected function showCompetitions(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource $externalSource,
        Sport $sport,
        League $league
    ): void {
        $externalLeague = $this->getter->getLeague(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league
        );
        $table = new ConsoleTable\Competitions();
        $competitions = array_values($externalSourceCompetitions->getCompetitions($sport, $externalLeague));
        $table->display($competitions);
    }

    protected function showTeams(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternSourceCompetitionStructureInterface $externalSourceCompetitionStructure,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season
    ): void {
        $competition = $this->getter->getCompetition(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league,
            $season
        );
        $table = new ConsoleTable\Teams();
        $table->display($externalSourceCompetitionStructure->getTeams($competition));
    }

    protected function showTeamCompetitors(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternSourceCompetitionStructureInterface $externalSourceCompetitionStructure,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season
    ): void {
        $competition = $this->getter->getCompetition(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league,
            $season
        );
        $table = new ConsoleTable\TeamCompetitors();
        $table->display($externalSourceCompetitionStructure->getTeamCompetitors($competition));
    }

    protected function showStructure(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternSourceCompetitionStructureInterface $externalSourceCompetitionStructure,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season
    ): void {
        $competition = $this->getter->getCompetition(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league,
            $season
        );

        $teamCompetitors = $externalSourceCompetitionStructure->getTeamCompetitors($competition);
        $table = new ConsoleTable\Structure();
        $structure = $externalSourceCompetitionStructure->getStructure($competition);
        $table->display($competition, $structure, $teamCompetitors);
    }

    protected function showAgainstGamesBasics(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternSourceCompetitionStructureInterface $externalSourceCompetitionStructure,
        ExternalSourceGamesAndPlayersInterface $externalSourceGamesAndPlayers,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season,
        SportRange $gameRoundRange
    ): void {
        $competition = $this->getter->getCompetition(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league,
            $season
        );

        $gameRoundNumbers = $externalSourceGamesAndPlayers->getGameRoundNumbers($competition);
        $games = [];
        for ($gameRoundNr = $gameRoundRange->getMin(); $gameRoundNr <= $gameRoundRange->getMax(); $gameRoundNr++) {
            if (count(
                    array_filter(
                        $gameRoundNumbers,
                        function (int $batchNrIt) use ($gameRoundNr): bool {
                            return $batchNrIt === $gameRoundNr;
                        }
                    )
                ) === 0) {
                $this->getLogger()->info('gameRoundNr "' . $gameRoundNr . '" komt niet voor in de externe bron');
            }
            $gameRoundGames = $externalSourceGamesAndPlayers->getAgainstGamesBasics($competition, $gameRoundNr);
            $games = array_merge($games, $gameRoundGames);
        }
        $teamCompetitors = $externalSourceCompetitionStructure->getTeamCompetitors($competition);
        $table = new ConsoleTable\AgainstGames();
        $table->display($competition, array_values($games), $teamCompetitors);
    }

    protected function showAgainstGame(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternSourceCompetitionStructureInterface $externalSourceCompetitionStructure,
        ExternalSourceGamesAndPlayersInterface $externalSourceGamesAndPlayers,
        ExternalSource $externalSource,
        Sport $sport,
        League $league,
        Season $season,
        string|int $gameId,
        bool $resetCache
    ): void {
        $competition = $this->getter->getCompetition(
            $externalSourceCompetitions,
            $externalSource,
            $sport,
            $league,
            $season
        );

        $externalGame = $this->getter->getAgainstGame(
            $externalSourceGamesAndPlayers,
            $externalSource,
            $competition,
            $gameId,
            $resetCache
        );

        $teamCompetitors = $externalSourceCompetitionStructure->getTeamCompetitors($competition);
        $structureNameService = new StructureNameService(new StartLocationMap($teamCompetitors));
        $table = new ConsoleTable\AgainstGame();
        $table->display($competition, $externalGame, $structureNameService);
    }

    protected function getExternalGameId(InputInterface $input, ExternalSource $externalSource): string|int
    {
        $externalGameId = $this->inputHelper->getIdFromInput($input, '');
        if (is_string($externalGameId) and strlen($externalGameId) > 0) {
            return $externalGameId;
        }

        $internalGameId = $this->inputHelper->getStringFromInput($input, 'internal-id');
        $againstGame = $this->againstGameRepos->find($internalGameId);
        if ($againstGame === null) {
            throw new \Exception('no externalid could be found', E_ERROR);
        }
        $externalId = $this->againstGameAttacherRepos->findOneByImportable($externalSource, $againstGame)?->getExternalId();
        if ($externalId === null) {
            throw new \Exception('no externalid could be found', E_ERROR);
        }
        return $externalId;
    }


}
