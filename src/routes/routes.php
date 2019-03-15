<?php

use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__ . '/job-application.php';
require __DIR__ . '/resumes.php';
require __DIR__ . '/letters.php';
require __DIR__ . '/job-ads.php';
require __DIR__ . '/employers.php';
require __DIR__ . '/submissions.php';
require __DIR__ . '/overview.php';


// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
