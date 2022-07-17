<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Output\Team\Player as TeamPlayerOutput;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SportsHelpers\Against\Side;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GameParticipations extends Command
{
    protected AgainstGameRepository $againstGameRepos;
    protected TeamPlayerRepository $teamPlayerRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var TeamPlayerRepository $teamPlayerRepos */
        $teamPlayerRepos = $container->get(TeamPlayerRepository::class);
        $this->teamPlayerRepos = $teamPlayerRepos;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:validate-game-participations')
            // the short description shown while running "php bin/console list"
            ->setDescription('validates the game-participations')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('validates the game-participations');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-validate-game-participations');
        $teamPlayerOutput = new TeamPlayerOutput($this->getLogger());
        try {
            $competitions = $this->competitionRepos->findAll();
            foreach ($competitions as $competition) {
                $seasonPeriod = $competition->getSeason()->getPeriod();
                $competitors = array_values($competition->getTeamCompetitors()->toArray());
                $startLocationMap = new StartLocationMap($competitors);
                $againstGameOutput = new AgainstGameOutput($startLocationMap, $this->getLogger());
                $games = $this->againstGameRepos->getCompetitionGames($competition);
                $count = 0;

                foreach ($games as $game) {
                    $againstGameOutput->output($game, 'validating ');
                    foreach ([Side::Home, Side::Away] as $side) {
                        foreach ($game->getSidePlaces($side) as $gamePlace) {
                            $startLocation = $gamePlace->getPlace()->getStartLocation();
                            if ($startLocation === null) {
                                throw new \Exception('startlocation could not be found', E_ERROR);
                            }
                            /** @var TeamCompetitor|null $teamCompetitor */
                            $teamCompetitor = $startLocationMap->getCompetitor($startLocation);
                            if ($teamCompetitor === null) {
                                throw new \Exception('team could not be found', E_ERROR);
                            }
                            foreach ($gamePlace->getParticipations() as $gameParticipation) {
                                $teamPlayer = $gameParticipation->getPlayer();
                                if ($teamPlayer->getTeam() !== $teamCompetitor->getTeam()) {
                                    $message = 'teams of player and gameparticipation are not equal';
                                    $this->getLogger()->error($message);
                                    continue;
                                }

                                if (!$teamPlayer->getPeriod()->contains($game->getStartDateTime())) {
                                    $message = 'game is outside playerperiod ' . $teamPlayerOutput->getString(
                                            $teamPlayer,
                                            ''
                                        );
                                    $this->getLogger()->error($message);
                                    continue;
                                }
                                if (!$seasonPeriod->contains($teamPlayer->getPeriod())) {
                                    $message = 'player-period ' . $teamPlayerOutput->getString(
                                            $teamPlayer,
                                            ''
                                        ) . ' is outside season ' . $seasonPeriod->toIso80000('Y-m-d');
                                    $this->getLogger()->error($message);
                                }
                            }
                        }
                    }

//                try {
//                    $teamPlayerOutput->output($teamPlayer, 'validating ' );
//                    $gameParticipations = $this->gameParticipationRepos->findBy(['player'=>$teamPlayer]);
//                    show number of games
//                } catch (\Exception $e) {
//                    $this->getLogger()->error($e->getMessage());
//                }

                    if ($count++ === 10) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }
}
