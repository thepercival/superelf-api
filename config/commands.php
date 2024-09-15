<?php

declare(strict_types=1);

use App\Commands\CompetitionConfig as CompetitionConfigCommand;
use App\Commands\ExternalSource\Get as GetExternalCommand;
use App\Commands\ExternalSource\Import as ImportCommand;
use App\Commands\ExternalSource\ImportImage as ImportImageCommand;
use App\Commands\Get as GetCommand;
use App\Commands\HelpCommand;
use App\Commands\Migration\Pools as MigratePoolsCommand;
use App\Commands\Migration\Users as MigrateUsersCommand;
use App\Commands\PersonCommand;
use App\Commands\PlayerTotals as UpdatePlayerTotalsCommand;
use App\Commands\PoolCompetitionsCommand;
use App\Commands\PoolUserCommand;
use App\Commands\PoolUserCopyCommand;
use App\Commands\Sync as SyncCommand;
use App\Commands\Transfer\CreateFormationsCommand;
use App\Commands\Validator\CompetitionConfig as ValidateCompetitionConfigCommand;
use App\Commands\Validator\Points as ValidatePointsCommand;
use App\Commands\Validator\GameParticipations as ValidateGameParticipationsCommand;
use App\Commands\Validator\TeamPlayers as ValidateTeamPlayersCommand;
use Psr\Container\ContainerInterface;

$commands = [
    "app:import" => function (ContainerInterface $container): ImportCommand {
        return new ImportCommand($container);
    },
    "app:import:image" => function (ContainerInterface $container): ImportImageCommand {
        return new ImportImageCommand($container);
    },
    "app:get-external" => function (ContainerInterface $container): GetExternalCommand {
        return new GetExternalCommand($container);
    },
    "app:get" => function (ContainerInterface $container): GetCommand {
        return new GetCommand($container);
    },
    "app:sync" => function (ContainerInterface $container): SyncCommand {
        return new SyncCommand($container);
    },
    "app:update-player-totals" => function (ContainerInterface $container): UpdatePlayerTotalsCommand {
        return new UpdatePlayerTotalsCommand($container);
    },
    "app:validate-game-participations" => function (ContainerInterface $container): ValidateGameParticipationsCommand {
        return new ValidateGameParticipationsCommand($container);
    },
    "app:validate-team-players" => function (
        ContainerInterface $container
    ): ValidateTeamPlayersCommand {
        return new ValidateTeamPlayersCommand($container);
    },
    "app:validate-points" => function (
        ContainerInterface $container
    ): ValidatePointsCommand {
        return new ValidatePointsCommand($container);
    },
    "app:validate-competitionconfig" => function (
        ContainerInterface $container
    ): ValidateCompetitionConfigCommand {
        return new ValidateCompetitionConfigCommand($container);
    },
    "app:admin-competitionconfigs" => function (ContainerInterface $container): CompetitionConfigCommand {
        return new CompetitionConfigCommand($container);
    },
    "app:person" => function (ContainerInterface $container): PersonCommand {
        return new PersonCommand($container);
    },
    "app:create-pool-competitions" => function (ContainerInterface $container): PoolCompetitionsCommand {
        return new PoolCompetitionsCommand($container);
    },
    "app:create-transfer-formations" => function (ContainerInterface $container): CreateFormationsCommand {
        return new CreateFormationsCommand($container);
    },
    "app:pooluser" => function (ContainerInterface $container): PoolUserCommand {
        return new PoolUserCommand($container);
    },
    "app:migrate-users" => function (ContainerInterface $container): MigrateUsersCommand {
        return new MigrateUsersCommand($container);
    },
    "app:migrate-pools" => function (ContainerInterface $container): MigratePoolsCommand {
        return new MigratePoolsCommand($container);
    },
];

$commands["app:help"] = function (ContainerInterface $container) use ($commands): HelpCommand {
    return new HelpCommand($container, array_keys($commands));
};

return $commands;
