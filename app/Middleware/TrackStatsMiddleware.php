<?php

namespace App\Middleware;

use App\Models\SiteStat;
use DateTime;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class TrackStatsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';

        $deviceType = 'Desktop';

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) {
            $deviceType = 'Mobile';
        }

        // Get platform/browser info from user agent
        $platform = 'Unknown';
        $browser = 'Unknown';


        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }


        $serverParams = $request->getServerParams();

        $ip =
            $request->getHeaderLine('CF-Connecting-IP')
            ?: $request->getHeaderLine('X-Forwarded-For')
            ?: $request->getHeaderLine('X-Real-IP')
            ?: ($serverParams['REMOTE_ADDR'] ?? null);

        // If X-Forwarded-For contains multiple IPs
        if ($ip && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $country = $request->getHeaderLine('CF-IPCountry');
        $country = ($country && $country !== 'XX') ? $country : null;



        SiteStat::create([
            'url' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'ip' => $ip ?? null,
            'device' => $deviceType,
            'platform' => $platform,
            'browser' => $browser,
            'country' => $country ?? null, # Cloudflare header
            'visited_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        return $handler->handle($request);
    }
}
