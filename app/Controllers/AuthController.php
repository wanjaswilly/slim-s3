<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    }

    /**
     * Show login page
     */
    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig', [
            'title' => 'Login - Sellers App'
        ]);
    }

    /**
     * Show registration page
     */
    public function showRegister(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/register.twig', [
            'title' => 'Create Account - Sellers App'
        ]);
    }

    /**
     * Handle login form submission
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = User::where('email', $email)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return $this->render($response, 'auth/login.twig', [
                'title' => 'Login - Sellers App',
                'error' => 'Invalid email or password',
                'old' => ['email' => $email]
            ]);
        }

        if (!$user->is_active) {
            return $this->render($response, 'auth/login.twig', [
                'title' => 'Login - Sellers App',
                'error' => 'Your account has been deactivated',
                'old' => ['email' => $email]
            ]);
        }

        // Generate JWT tokens
        $tokens = $this->generateTokens($user);

        // Set HTTP-only cookie for web access
        setcookie('access_token', $tokens['access_token'], [
            'expires' => time() + (15 * 60), // 15 minutes
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        setcookie('refresh_token', $tokens['refresh_token'], [
            'expires' => time() + (30 * 24 * 60 * 60), // 30 days
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Store refresh token in database for validation
        DB::table('refresh_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'token' => password_hash($tokens['refresh_token'], PASSWORD_DEFAULT),
                'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );

        // Redirect to dashboard
        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }

    /**
     * Handle registration form submission
     */
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate required fields
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }

        // Check if user already exists
        if (User::where('email', $data['email'])->exists()) {
            $errors['email'] = 'User already exists with this email';
        }

        // If there are errors, return to form
        if (!empty($errors)) {
            return $this->render($response, 'auth/register.twig', [
                'title' => 'Create Account - Sellers App',
                'errors' => $errors,
                'old' => $data
            ]);
        }

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'seller',
            'is_active' => true
        ]);

        // Generate JWT tokens
        $tokens = $this->generateTokens($user);

        // Set HTTP-only cookies
        setcookie('access_token', $tokens['access_token'], [
            'expires' => time() + (15 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        setcookie('refresh_token', $tokens['refresh_token'], [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Store refresh token in database
        DB::table('refresh_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'token' => password_hash($tokens['refresh_token'], PASSWORD_DEFAULT),
                'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)),
                'created_at' => date('Y-m-d H:i:s')
            ]
        );

        // Redirect to dashboard
        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request, Response $response): Response
    {
        // Clear cookies
        setcookie('access_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        setcookie('refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Remove refresh token from database if user is authenticated
        $user = $request->getAttribute('user');
        if ($user) {
            DB::table('refresh_tokens')->where('user_id', $user->id)->delete();
        }

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Request $request, Response $response): Response
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        try {
            // Verify refresh token signature
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, 'HS256'));
            
            // Check if it's a refresh token
            if (!isset($decoded->type) || $decoded->type !== 'refresh') {
                throw new \Exception('Invalid token type');
            }

            // Get stored refresh token from database
            $storedToken = DB::table('refresh_tokens')
                ->where('user_id', $decoded->sub)
                ->first();

            if (!$storedToken || !password_verify($refreshToken, $storedToken->token)) {
                throw new \Exception('Invalid refresh token');
            }

            // Check if refresh token is expired
            if (strtotime($storedToken->expires_at) < time()) {
                DB::table('refresh_tokens')->where('user_id', $decoded->sub)->delete();
                throw new \Exception('Refresh token expired');
            }

            // Get user
            $user = User::find($decoded->sub);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Generate new tokens
            $tokens = $this->generateTokens($user);

            // Update cookies
            setcookie('access_token', $tokens['access_token'], [
                'expires' => time() + (15 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            setcookie('refresh_token', $tokens['refresh_token'], [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            // Update refresh token in database
            DB::table('refresh_tokens')
                ->where('user_id', $user->id)
                ->update([
                    'token' => password_hash($tokens['refresh_token'], PASSWORD_DEFAULT),
                    'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            // For API requests, return JSON response
            $acceptHeader = $request->getHeaderLine('Accept');
            if (strpos($acceptHeader, 'application/json') !== false) {
                $response->getBody()->write(json_encode([
                    'status' => 'success',
                    'data' => [
                        'access_token' => $tokens['access_token'],
                        'expires_in' => 15 * 60
                    ]
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            // For web requests, redirect back
            return $response
                ->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/dashboard')
                ->withStatus(302);

        } catch (\Exception $e) {
            // Clear invalid tokens
            setcookie('access_token', '', ['expires' => time() - 3600, 'path' => '/']);
            setcookie('refresh_token', '', ['expires' => time() - 3600, 'path' => '/']);

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }
    }

    /**
     * Generate JWT tokens
     */
    private function generateTokens(User $user): array
    {
        $now = time();
        
        // Access token (15 minutes)
        $accessToken = JWT::encode([
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => $now,
            'exp' => $now + (15 * 60),
            'sub' => $user->id,
            'role' => $user->role,
            'type' => 'access'
        ], $this->secretKey, 'HS256');

        // Refresh token (30 days)
        $refreshToken = JWT::encode([
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => $now,
            'exp' => $now + (30 * 24 * 60 * 60),
            'sub' => $user->id,
            'type' => 'refresh'
        ], $this->secretKey, 'HS256');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * Render Twig template
     */
    private function render(Response $response, string $template, array $data = []): Response
    {
        $twig = $GLOBALS['twig'] ?? null;
        
        if (!$twig) {
            throw new \RuntimeException('Twig not available');
        }

        $response->getBody()->write($twig->render($template, $data));
        return $response->withHeader('Content-Type', 'text/html');
    }
}