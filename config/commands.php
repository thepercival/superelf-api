<?php

declare(strict_types=1);

use App\Commands\CompetitionConfig as CompetitionConfigCommand;
use App\Commands\ExternalSource\Get as GetExternalCommand;
use App\Commands\ExternalSource\Import as ImportCommand;
use App\Commands\ExternalSource\ImportImage as ImportImageCommand;
use App\Commands\Get as GetCommand;
use App\Commands\Listing as ListingCommand;
use App\Commands\PlayerTotals as UpdatePlayerTotalsCommand;
use App\Commands\Sync as SyncCommand;
use App\Commands\Validator\GameParticipations as ValidateGameParticipationsCommand;
use App\Commands\Validator\PersonPlayerPeriods as ValidatePersonPlayerPeriodsCommand;
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
    "app:validate-person-playerperiods" => function (ContainerInterface $container
    ): ValidatePersonPlayerPeriodsCommand {
        return new ValidatePersonPlayerPeriodsCommand($container);
    },
    "app:competitionconfig" => function (ContainerInterface $container): CompetitionConfigCommand {
        return new CompetitionConfigCommand($container);
    }

];

$commands["app:list"] = function (ContainerInterface $container) use ($commands): ListingCommand {
    return new ListingCommand($container, array_keys($commands));
};

return $commands;
