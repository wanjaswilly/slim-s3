<?php

namespace App\Controllers;

use App\Models\SiteStat;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write("Hello from HomeController");
        return $response;
    }

    public function about(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/about.twig');
    }


    public function home(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/home.twig');
    }


    public function contact(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/contact.twig');
    }


    public function terms(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/terms.twig');
    }


    public function privacy(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/privacy.twig');
    }


    public function stats(Request $request, Response $response): Response
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'pages/stats.twig',[
            'stats' => SiteStat::get()
        ]);
    }

}