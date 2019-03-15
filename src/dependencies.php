<?php
use Serve\Helpers\ResponseBody;
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
    $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
return $pdo;
};

$container['errorHandler'] = function ($appContext) {
    return function ($request, $response, $exception) use ($appContext) {
        $logger = $appContext->get('logger');
        $logger->info('xxxxxxxxxxxxxxxx Error Handler: Exception xxxxxxxxxxxxxxxxx');
        $logger->info('Error: exception');
        $logger->info('    message: ' . $exception->getMessage());
        $logger->info('    code: ' . $exception->getCode());
        $logger->info('    file: ' . $exception->getFile());
        $logger->info('    line: ' . $exception->getLine());
        $logger->info('    trace: ' . json_encode($exception->getTrace()));
        $logger->info('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    
        $ret = ResponseBody::fromException($exception);
        return $response->withJson($ret());
    };
};
$container['phpErrorHandler'] = function ($appContext) {
    return function ($request, $response, $exception) use ($appContext) {
        $logger = $appContext->get('logger');
        $logger->info('xxxxxxxxxxxxxxxx PHP Error Handler: Exception xxxxxxxxxxxxxxxxx');
        $logger->info('Error: exception');
        $logger->info('    message: ' . $exception->getMessage());
        $logger->info('    code: ' . $exception->getCode());
        $logger->info('    file: ' . $exception->getFile());
        $logger->info('    line: ' . $exception->getLine());
        $logger->info('    trace: ' . json_encode($exception->getTrace()));
        $logger->info('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    
        $ret = ResponseBody::fromException($exception);
        return $response->withJson($exception);
    };
};

