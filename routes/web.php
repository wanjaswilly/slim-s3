<?php

use Slim\App;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Static core routes
    $pages = [
        '/'          => 'home',
        '/developer' => 'developer',
        '/support'   => 'support',
    ];

    foreach ($pages as $route => $template) {
        $app->get($route, function (Request $request, Response $response) use ($template) {
            $view = Twig::fromRequest($request);
            return $view->render($response, "pages/{$template}.twig");
        });
    }

    //  AUTO-GENERATED ROUTES - DO NOT REMOVE THIS LINE
    // $app->get('/example', fn($req, $res) => $this->get(Twig::class)->render($res, 'pages/example.twig'));

    // Test 500 error
    $app->get('/test-500', function () {
        throw new \Exception("Intentional test error");
    });
};
