<?php

declare(strict_types=1);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'environment' => getenv('ENVIRONMENT'),
    'displayErrorDetails' => (getenv('ENVIRONMENT') === "development"),
    // Renderer settings
    'renderer' => [
        'template_path' => __DIR__ . '/../templates/',
    ],
    // Serializer(JMS)
    'serializer' => [
        'cache_dir' => __DIR__ . '/../cache/serializer',
        'yml_dir' => [
            "Sports" => __DIR__ . '/../vendor/thepercival/php-sports/serialization/yml',
            "SportsImport" => __DIR__ . '/../vendor/thepercival/php-sports-import/serialization/yml',
            "SuperElf" => __DIR__ . '/../serialization/yml'
        ],
    ],
    // Monolog settings
    'logger' => [
        'path' => __DIR__ . '/../logs/',
        'level' => (getenv('ENVIRONMENT') === "development" ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR),
    ],
    'router' => [
        'cache_file' => __DIR__ . '/../cache/router',
    ],
    // Doctrine settings
    'doctrine' => [
        'meta' => [
            'entity_path' => [
                __DIR__ . '/../vendor/thepercival/php-sports/db/doctrine-mappings',
                __DIR__ . '/../vendor/thepercival/php-sports-import/db/doctrine-mappings',
                __DIR__ . '/../db/doctrine-mappings'
            ],
            'dev_mode' => (getenv('ENVIRONMENT') === "development"),
            'proxy_dir' => __DIR__ . '/../cache/proxies',
            'cache' => null,
        ],
        'connection' => [
            'driver' => 'pdo_mysql',
            'host' => getenv('DB_HOST'),
            'dbname' => getenv('DB_NAME'),
            'user' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
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
        'jwtsecret' => getenv('JWT_SECRET'),
        'jwtalgorithm' => getenv('JWT_ALGORITHM'),
        'validatesecret' => getenv('VALIDATE_SECRET'),
    ],
    'www' => [
        'wwwurl' => getenv('WWW_URL'),
        'wwwurl-localpath' => realpath(__DIR__ . "/../../") . "/superelf/dist/",
        'apiurl' => getenv('API_URL'),
        "apiurl-localpath" => realpath(__DIR__ . '/../public/') . '/',
    ],
    'email' => [
        'from' => "info@superelf-eredivisie.nl",
        'fromname' => "SuperElf",
        'admin' => "coendunnink@gmail.com",
        'mailtrap' => [
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => 2525,
            'smtp_user' => getenv('MAILTRAP_USER'),
            'smtp_pass' => getenv('MAILTRAP_PASSWORD')
        ]
    ],
    'images' => [
        'personsSuffix' => 'images/persons/',
        'teamsSuffix' => 'images/teams/',
    ],
    'proxy' => [
        'host' => getenv('EXTERNAL_PROXY_HOST'),
        'port' => getenv('EXTERNAL_PROXY_PORT'),
        'username' => getenv('EXTERNAL_PROXY_USERNAME'),
        'password' => getenv('EXTERNAL_PROXY_PASSWORD'),
    ],
    'periods' => [
        'createAndJoinStart' => getenv('PERIODSTART_CREATE_AND_JOIN'),
        'assembleStart' => getenv('PERIODSTART_ASSEMBLE'),
        'assembleEnd' => getenv('PERIODEND_ASSEMBLE'),
        'transfersStart' => getenv('PERIODSTART_TRANSFER'),
        'transfersEnd' => getenv('PERIODEND_TRANSFER'),
    ],
    'availableFormationNames' => ['1-3-4-3', '1-3-5-2', '1-4-3-3', '1-4-4-2', '1-5-3-2'],
    'defaultMaxNrOfTransfers' => getenv('DEFAULT_MAXNROFTRANSFERS'),
    'scoreunits' => [
        \SuperElf\ScoreUnit::POINTS_WIN => getenv('SCOREUNIT_POINTS_WIN'),
        \SuperElf\ScoreUnit::POINTS_DRAW => getenv('BETPOINTS_POINTS_DRAW'),
        \SuperElf\ScoreUnit::GOAL_GOALKEEPER => getenv('SCOREUNIT_GOAL_GOALKEEPER'),
        \SuperElf\ScoreUnit::GOAL_DEFENDER => getenv('SCOREUNIT_GOAL_DEFENDER'),
        \SuperElf\ScoreUnit::GOAL_MIDFIELDER => getenv('SCOREUNIT_GOAL_MIDFIELDER'),
        \SuperElf\ScoreUnit::GOAL_FORWARD => getenv('SCOREUNIT_GOAL_FORWARD'),
        \SuperElf\ScoreUnit::ASSIST_GOALKEEPER => getenv('SCOREUNIT_ASSIST_GOALKEEPER'),
        \SuperElf\ScoreUnit::ASSIST_DEFENDER => getenv('SCOREUNIT_ASSIST_DEFENDER'),
        \SuperElf\ScoreUnit::ASSIST_MIDFIELDER => getenv('SCOREUNIT_ASSIST_MIDFIELDER'),
        \SuperElf\ScoreUnit::ASSIST_FORWARD => getenv('SCOREUNIT_ASSIST_FORWARD'),
        \SuperElf\ScoreUnit::GOAL_PENALTY => getenv('SCOREUNIT_GOAL_PENALTY'),
        \SuperElf\ScoreUnit::GOAL_OWN => getenv('SCOREUNIT_GOAL_OWN'),
        \SuperElf\ScoreUnit::SHEET_CLEAN_GOALKEEPER => getenv('SCOREUNIT_SHEET_CLEAN_GOALKEEPER'),
        \SuperElf\ScoreUnit::SHEET_CLEAN_DEFENDER => getenv('SCOREUNIT_SHEET_CLEAN_DEFENDER'),
        \SuperElf\ScoreUnit::SHEET_SPOTTY_GOALKEEPER => getenv('SCOREUNIT_SHEET_SPOTTY_GOALKEEPER'),
        \SuperElf\ScoreUnit::SHEET_SPOTTY_DEFENDER => getenv('SCOREUNIT_SHEET_SPOTTY_DEFENDER'),
        \SuperElf\ScoreUnit::CARD_YELLOW => getenv('SCOREUNIT_CARD_YELLOW'),
        \SuperElf\ScoreUnit::CARD_RED => getenv('SCOREUNIT_CARD_RED'),
    ],
];
