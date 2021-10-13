<?php
declare(strict_types=1);

use Psr\Container\ContainerInterface;

use App\Commands\Listing as ListingCommand;
use App\Commands\ExternalSource\Import as ImportCommand;
use App\Commands\ExternalSource\Get as GetExternalCommand;
use App\Commands\Get as GetCommand;
use App\Commands\Sync as SyncCommand;

$commands = [
    "app:import" => function (ContainerInterface $container): ImportCommand {
        return new ImportCommand($container);
    },
    "app:get-external" => function (ContainerInterface $container): GetExternalCommand {
        return new GetExternalCommand($container);
    },
    "app:get" => function (ContainerInterface $container): GetCommand {
        return new GetCommand($container);
    },
    "app:sync" => function (ContainerInterface $container): SyncCommand {
        return new SyncCommand($container);
    }
];

$commands["app:list"] = function (ContainerInterface $container) use ($commands) : ListingCommand {
    return new ListingCommand($container, array_keys($commands));
};

return $commands;
