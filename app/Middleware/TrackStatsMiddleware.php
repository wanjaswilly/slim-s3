<?php
// middleware/TrackStatsMiddleware.php

use App\Models\SiteStat;

return function ($request, $handler) {
    $response = $handler->handle($request);

    $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';
    
    // Improved device detection
    $deviceType = 'Desktop';
    if (preg_match(
        '/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i', 
        strtolower($userAgent)
    )) {
        $deviceType = 'Mobile';
    } elseif (preg_match(
        '/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', 
        strtolower($userAgent))
    ) {
        $deviceType = 'Tablet';
    }

    // Get platform/browser info from user agent
    $platform = 'Unknown';
    $browser = 'Unknown';
    
    if (preg_match('/linux/i', $userAgent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $userAgent)) {
        $platform = 'Windows';
    }
    
    if (preg_match('/msie/i', $userAgent) && !preg_match('/opera/i', $userAgent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/safari/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/opera/i', $userAgent)) {
        $browser = 'Opera';
    } elseif (preg_match('/edge/i', $userAgent)) {
        $browser = 'Edge';
    }

    SiteStat::create([
        'url' => $request->getUri()->getPath(),
        'method' => $request->getMethod(),
        'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
        'device' => $deviceType,
        'platform' => $platform,
        'browser' => $browser,
        'country' => $request->getHeaderLine('CF-IPCountry') ?? null, # Cloudflare header
        'visited_at' => (new DateTime())->format('Y-m-d H:i:s'),
    ]);

    return $response;
};