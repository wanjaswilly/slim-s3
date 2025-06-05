<?php


use Slim\App;
use Slim\Views\Twig;

return function (App $app) {
    $pages = [
        '/' => 'home',
        '/about' => 'about',
        '/contact' => 'contact',
        '/mission' => 'mission',
        '/vision' => 'vision',
        '/team' => 'team',
        '/partners' => 'partners'
    ];

    foreach ($pages as $route => $template) {
        $app->get($route, function ($request, $response, $args) use ($template) {
            $view = Twig::fromRequest($request);
            return $view->render($response, "pages/{$template}.twig");
        });
    }
};
