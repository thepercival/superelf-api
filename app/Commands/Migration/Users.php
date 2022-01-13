<?php

namespace App\Commands\Migration;

use App\Command;
use Doctrine\DBAL\Connection as DBConnection;
use Psr\Container\ContainerInterface;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:competitionconfig create --league=Eredivisie --season=2014/2015 --createAndJoinStart="2014-07-23 12:00" --assemblePeriod="2014-08-23 12:00=>2014-09-23 12:00" --assemblePeriod="2015-02-01 12:00=>2015-02-03 12:00" --loglevel=200
 * php bin/console.php app:competitionconfig create --league=Eredivisie --season=2015/2016 --createAndJoinStart="2015-07-31 12:00" --assemblePeriod="2015-09-01 06:00=>2015-09-12 16:00" --transferPeriod="2016-02-01 06:00=>2016-02-05 18:30" --loglevel=200
 */
class Users extends Command
{
    protected UserRepository $userRepos;
    protected DBConnection $migrationConn;

    public function __construct(ContainerInterface $container)
    {
        /** @var UserRepository $userRepos */
        $userRepos = $container->get(UserRepository::class);
        $this->userRepos = $userRepos;

        /** @var DBConnection $migrationConn */
        $migrationConn = $container->get(DBConnection::class);
        $this->migrationConn = $migrationConn;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:migrate-users')
            ->setDescription('migrates the users')
            ->setHelp('migrates the users');


//        $f = CompetitionConfig::DateTimeFormat;
//        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
//        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
//        $this->addOption('createAndJoinStart', null, InputOption::VALUE_REQUIRED, $f);
//        $this->addOption('assemblePeriod', null, InputOption::VALUE_REQUIRED, $f . '=>' . $f);
//        $this->addOption('transferPeriod', null, InputOption::VALUE_REQUIRED, $f . '=> ' . $f);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-migrate-users');

        try {
            $stmt = $this->migrationConn->executeQuery(
                'select * from UsersExt'
            );
            while (($row = $stmt->fetchAssociative()) !== false) {
                if ($row['ActivationKey'] !== null && strlen($row['ActivationKey'] > 0)) {
                    continue;
                }

                $newUser = new User(
                    $row['EmailAddress'],
                    mb_substr($row['LoginName'], 0, 15),
                    '',
                    $row['Password'],
                );
                $newUser->setValidated(true);
                $this->userRepos->save($newUser);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }



//    protected function create(InputInterface $input): int
//    {
//        $competition = $this->getCompetitionFromInput($input);
//        if ($competition === null) {
//            throw new \Exception('competition not found', E_ERROR);
//        }
//        $admin = $this->getAdministrator($competition);
//        $competitionConfig = $admin->create(
//            $competition,
//            $this->getDateTimeFromInput($input, 'createAndJoinStart'),
//            $this->getPeriodFromInput($input, 'assemblePeriod'),
//            $this->getPeriodFromInput($input, 'transferPeriod'),
//            $this->againstGameRepos->getCompetitionGames($competition)
//        );
//        $this->competitionConfigRepos->save($competitionConfig);
//        $this->getLogger()->info('competitionConfig created and saved');
//        // throw new \Exception('implement', E_ERROR);
//        return 0;
//    }
}
