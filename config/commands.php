<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

use App\Commands\Listing as ListingCommand;
use App\Commands\Import as ImportCommand;
use App\Commands\GetExternal as GetExternalCommand;

$commands = [
    "app:import" => function (ContainerInterface $container): ImportCommand {
        return new ImportCommand($container);
    },
    "app:getexternal" => function (ContainerInterface $container): GetExternalCommand {
        return new GetExternalCommand($container);
    }
];

$commands["app:list"] = function (ContainerInterface $container) use($commands) : ListingCommand {
    return new ListingCommand($container, array_keys( $commands) );
};

return $commands;
