<?php

declare(strict_types=1);

use Monolog\Logger;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'environment' => $_ENV['ENVIRONMENT'],
    'displayErrorDetails' => ($_ENV['ENVIRONMENT'] === "development"),
    // Renderer settings
    'renderer' => [
        'template_path' => __DIR__ . '/../templates/',
    ],
    // Serializer(JMS)
    'serializer' => [
        'cache_dir' => __DIR__ . '/../cache/serializer',
        'yml_dir' => [
            'SportsHelpers' => __DIR__ . '/../vendor/thepercival/php-sports-helpers/serialization/yml',
            "Sports" => __DIR__ . '/../vendor/thepercival/php-sports/serialization/yml',
            "SportsImport" => __DIR__ . '/../vendor/thepercival/php-sports-import/serialization/yml',
            "SuperElf" => __DIR__ . '/../serialization/yml'
        ],
    ],
    // Monolog settings
    'logger' => [
        'path' => __DIR__ . '/../logs/',
        'level' => ($_ENV['ENVIRONMENT'] === "development" ? Logger::DEBUG : Logger::ERROR),
    ],
    'router' => [
        'cache_file' => __DIR__ . '/../cache/router',
    ],
    // Doctrine settings
    'doctrine' => [
        'meta' => [
            'entity_path' => [
                __DIR__ . '/../vendor/thepercival/php-sports-helpers/db/doctrine-mappings',
                __DIR__ . '/../vendor/thepercival/php-sports-planning/db/doctrine-mappings',
                __DIR__ . '/../vendor/thepercival/php-sports/db/doctrine-mappings',
                __DIR__ . '/../vendor/thepercival/php-sports-import/db/doctrine-mappings',
                __DIR__ . '/../db/doctrine-mappings'
            ],
            'dev_mode' => ($_ENV['ENVIRONMENT'] === "development"),
            'proxy_dir' => __DIR__ . '/../cache/proxies',
            'cache' => null,
        ],
        'migrationconnection' => [
            'driver' => 'pdo_mysql',
            'host' => $_ENV['DB_HOST'],
            'dbname' => $_ENV['DB_MIGRATION_NAME'],
            'user' => $_ENV['DB_MIGRATION_USERNAME'],
            'password' => $_ENV['DB_MIGRATION_PASSWORD'],
            'charset' => 'utf8mb4',
            'driverOptions' => array(
                1002 => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'"
            )
        ],
        'connection' => [
            'driver' => 'pdo_mysql',
            'host' => $_ENV['DB_HOST'],
            'dbname' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => 'utf8mb4',
            'driverOptions' => array(
                1002 => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'"
            )
        ],
        'serializer' => array(
            'enabled' => true
        ),
    ],
    'auth' => [
        'jwtsecret' => $_ENV['JWT_SECRET'],
        'jwtalgorithm' => $_ENV['JWT_ALGORITHM'],
        'validatesecret' => $_ENV['VALIDATE_SECRET'],
    ],
    'www' => [
        'wwwurl' => $_ENV['WWW_URL'],
        'wwwurl-localpath' => realpath(__DIR__ . "/../../") . "/superelf/dist/",
        'apiurl' => $_ENV['API_URL'],
        "apiurl-localpath" => realpath(__DIR__ . '/../public/') . '/',
    ],
    'email' => [
        'from' => "info@superelf-eredivisie.nl",
        'fromname' => "SuperElf",
        'admin' => "coendunnink@gmail.com",
        'mailtrap' => [
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => 2525,
            'smtp_user' => $_ENV['MAILTRAP_USER'],
            'smtp_pass' => $_ENV['MAILTRAP_PASSWORD']
        ]
    ],
    'images' => [
        'playersSuffix' => 'images/players/',
        'teamsSuffix' => 'images/teams/',
    ],
    'proxy' => [
        'host' => $_ENV['EXTERNAL_PROXY_HOST'],
        'port' => $_ENV['EXTERNAL_PROXY_PORT'],
        'username' => $_ENV['EXTERNAL_PROXY_USERNAME'],
        'password' => $_ENV['EXTERNAL_PROXY_PASSWORD'],
    ],
    'queue' => [
        'host' => 'localhost',
        'port' => 5672,
        'vhost' => '/',
        'user' => 'guest',
        'pass' => 'guest',
        'persisted' => false,
        'suffix' => $_ENV['QUEUE_NAME_SUFFIX']
    ],
    'availableFormations' => ['1-3-4-3', '1-3-5-2', '1-4-3-3', '1-4-4-2', '1-5-3-2']
];
