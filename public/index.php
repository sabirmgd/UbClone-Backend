<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



require __DIR__ . '/../vendor/autoload.php';
session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);


// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';



$pdo = $app->getContainer()->get('db');

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => ["/passenger_api/login/","/passenger_api/email_verification/","/passenger_api/driver/","/passenger_api/requests/","/passenger_api/cancel/","/passenger_api/arrived/"],
    "secure" => false,
    "authenticator" => new \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator([
        "pdo" => $pdo,
        "table" => "passengers",
        "user" => "email",
        "hash" => "password"
    ]),
    "callback" => function ($request, $response, $arguments) {
        global $userInfo; 
        $userInfo['email'] = $arguments['user'];
    }, 
    "error" => function ($request, $response, $arguments) {
        $data = [];
        $data["status"] = "1";
        $data["error_msg"] = "Authentication failure";
        return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    }
]));

// Another authenticator for the drivers
$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => ["/driver_api/login/","/driver_api/requests/","/driver_api/accept/"],
    "secure" => false,
    "authenticator" => new \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator([
        "pdo" => $pdo,
        "table" => "drivers",
        "user" => "email",
        "hash" => "password"
    ]),
    "callback" => function ($request, $response, $arguments) {
        global $userInfo; 
        $userInfo['email'] = $arguments['user'];
    }, 
    "error" => function ($request, $response, $arguments) {
        $data = [];
        $data["status"] = "1";
        $data["error_msg"] = "Authentication failure";
        return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    }
]));





// Run app
$app->run();
