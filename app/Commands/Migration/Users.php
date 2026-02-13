<?php

namespace App\Commands\Migration;

use App\Command;
use Doctrine\DBAL\Connection as DBConnection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use SuperElf\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:migrate-pools --loglevel=200
 */
class Users extends Command
{
    /** @var EntityRepository<<User>  */
    protected EntityRepository $userRepos;
    protected DBConnection $migrationConn;
    protected EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container)
    {
        /** @var EntityManagerInterface entityManager */
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->userRepos = $this->entityManager->getRepository(User::class);

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
                $this->entityManager->persist($newUser);
                $this->entityManager->flush();
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
