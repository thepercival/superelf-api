<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

use App\Commands\Listing as ListingCommand;
use App\Commands\Import as ImportCommand;
use App\Commands\GetExternal as GetExternalCommand;
use App\Commands\Get as GetCommand;
use App\Commands\Calculate\PersonStats as PersonStatsCommand;

$commands = [
    "app:import" => function (ContainerInterface $container): ImportCommand {
        return new ImportCommand($container);
    },
    "app:getexternal" => function (ContainerInterface $container): GetExternalCommand {
        return new GetExternalCommand($container);
    },
    "app:get" => function (ContainerInterface $container): GetCommand {
        return new GetCommand($container);
    },
    "app:calculate-personstats" => function (ContainerInterface $container): PersonStatsCommand {
        return new PersonStatsCommand($container);
    }
];

$commands["app:list"] = function (ContainerInterface $container) use($commands) : ListingCommand {
    return new ListingCommand($container, array_keys( $commands) );
};

return $commands;
