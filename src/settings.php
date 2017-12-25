<?php

$Loader = (new josegonzalez\Dotenv\Loader(__DIR__ . '/../.env'))
    // Parse the .env file
    ->parse()
    // Send the parsed .env file to the $_ENV variable
    ->toEnv();

return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' =>
                ($_ENV['AURESS_VIEWER_ENVIRONMENT'] ?? 'dev') === 'prod'
                    ? __DIR__ . '/../.view-cache/'
                    : false,
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Database credentials
        'db' => [
            'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_DATABASE'] ?? 'AuResS',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? 'root',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => $_ENV['DB_PREFIX'] ?? '',
        ],
    ],
];
