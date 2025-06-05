<?php


use Slim\App;
use Slim\Views\Twig;

return function (App $app) {
    $pages = [
        '/' => 'home',
        '/developer' => 'developer',
        '/support' => 'support',
    ];

    foreach ($pages as $route => $template) {
        $app->get($route, function ($request, $response, $args) use ($template) {
            $view = Twig::fromRequest($request);
            return $view->render($response, "pages/{$template}.twig");
        });
    }
    $app->get('/test-500', function ($request, $response) {
        throw new \Exception("Intentional test error");
    });
};


