<?php

use App\Middleware\TrackStatsMiddleware;
use App\Models\SiteStat;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\TwigFunction;

require __DIR__ . '/vendor/autoload.php';

session_start();

$app = AppFactory::create();
// Load configuration files
$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection($dbConfig['connections'][$dbConfig['default']]);

// Make Eloquent available globally (optional)
$capsule->setAsGlobal();

// Boot Eloquent
$capsule->bootEloquent(); 

/*
 * Stats tracking moved to middleware for proper $request context.
 * See App\Middleware\TrackStatsMiddleware.
 */

$twig = Twig::create(__DIR__ . '/templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));


$baseAssetUrl = $_ENV['ASSET_BASE'];

$twig->getEnvironment()->addFunction(new TwigFunction('asset', function ($path) use ($baseAssetUrl) {
    return'/' . ltrim($path, '/');
}));

foreach ([
    'APP_URL', 'APP_NAME', 'ASSET_BASE', 'APP_ENVIRONMENT'
] as $key) {
    $twig->getEnvironment()->addGlobal($key, $_ENV[$key] ?? '');
}

$app->add(new \App\Middleware\TrackStatsMiddleware());


$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// 404 Not Found
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app) {
    $view = Twig::fromRequest($request);
    $response = new \Slim\Psr7\Response();
    return $view->render($response->withStatus(404), 'errors/404.twig');
});

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($twig): Response {
    $response = new \Slim\Psr7\Response();

    return $twig->render($response, 'errors/500.twig', [
        'message' => $exception->getMessage(),
        // 'message' => $exception
    ])->withStatus(500);
});


(require __DIR__ . '/routes/web.php')($app);

return $app;
