<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SportsImport\Getter as ImportGetter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PersonPlayerPeriods extends Command
{
    protected ImportGetter $getter;
    protected AgainstGameRepository $againstGameRepos;
    protected TeamPlayerRepository $teamPlayerRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var ImportGetter $getter */
        $getter = $container->get(ImportGetter::class);
        $this->getter = $getter;

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
            ->setName('app:validate-person-playerperiods')
            // the short description shown while running "php bin/console list"
            ->setDescription('validates the person-playerperiods')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('validates the person-playerperiods');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-validate-person-playerperiods');

        $this->getLogger()->info('check if there are no overlapping player periods');

        // maak aparte validators voor deze en gameparticipations. Deze kan dan gedraaid worden na de sync
        // geef aan deze validator een aantal personen op en geef aan de gameparticipations aan games mee

//        $teamPlayerOutput = new TeamPlayerOutput($this->getLogger());
//
//        try {
//            $teamPlayers = $this->teamPlayerRepos->findAll();
//            $count = 0;
//            foreach( $teamPlayers as $teamPlayer) {
        ////            $message = 'voor "' . $externalSourceName . '" kan er geen externe bron worden gevonden';
        ////            $this->getLogger()->error($message);
        ////            return -1;
//
//
//
//                try {
//                    $teamPlayerOutput->output($teamPlayer, 'validating ' );
//                    $gameParticipations = $this->gameParticipationRepos->findBy(['player'=>$teamPlayer]);
//                    show number of games
//                } catch (\Exception $e) {
//                    $this->getLogger()->error($e->getMessage());
//                }
//
//                if( $count++ === 10 ) {
//                    break;
//                }
//            }
//        } catch (\Exception $e) {
//            if ($this->logger !== null) {
//                $this->logger->error($e->getMessage());
//            }
//        }
        return 0;
    }
}
