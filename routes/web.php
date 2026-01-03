<?php

use App\Controllers\HomeController;
use App\Models\SiteStat;
use Slim\App;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Static core routes
    // Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });

    # Route for 'about'
    $app->get('/about', [HomeController::class, 'about'])->setName('about');

    # Route for 'home'
    $app->get('/', [HomeController::class, 'home'])->setName('home');

    # Route for 'contact'
    $app->get('/contact', [HomeController::class, 'contact'])->setName('contact');

    # Route for 'terms'
    $app->get('/terms', [HomeController::class, 'terms'])->setName('terms');

    # Route for 'privacy'
    $app->get('/privacy', [HomeController::class, 'privacy'])->setName('privacy');


    # Route for 'stats'
    $app->get('/stats', [HomeController::class, 'stats'])->setName('stats');
};
