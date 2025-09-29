<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class AuthMiddleware
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        // Get token from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // If no token in header, check cookie
        if (!$token && isset($_COOKIE['access_token'])) {
            $token = $_COOKIE['access_token'];
        }

        if (!$token) {
            return $this->unauthorizedResponse('No token provided');
        }

        try {
            // Decode and verify token
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Check if token is expired
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return $this->unauthorizedResponse('Token has expired');
            }

            // Get user from database
            $user = User::find($decoded->sub);
            if (!$user) {
                return $this->unauthorizedResponse('User not found');
            }

            if (!$user->is_active) {
                return $this->unauthorizedResponse('Account deactivated');
            }

            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('jwt', $decoded);

            return $handler->handle($request);

        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Invalid token: ' . $e->getMessage());
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new \Slim\Psr7\Response();
        
        // Check if it's an API request
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($acceptHeader, 'application/json') !== false) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'code' => 401,
                'message' => $message
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // For web requests, redirect to login
        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}