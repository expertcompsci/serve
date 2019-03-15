<?php
return [
    'settings' => [
        'unlinkDirectory' => __DIR__ . 'C:\Users\Ben\dev\jobsearcher\www\serve\uploads',
        'uploadDirectory' => __DIR__ . '/../uploads/',
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'determineRouteBeforeAppMiddleware' => true,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::INFO,
        ],
        'db' => [
            'host' => 'localhost:3306',
            'user' => 'psalteco_ben',
            'pass' => 'psalms100*',
            'dbname' => 'psalteco_test',
        ],
    ],
];
