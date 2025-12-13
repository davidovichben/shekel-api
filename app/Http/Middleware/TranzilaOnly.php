<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TranzilaOnly
{
    /**
     * Tranzila's known IP addresses.
     * These should be verified with Tranzila's documentation.
     */
    protected array $allowedIps = [
        '192.118.32.0/24',   // Tranzila IP range
        '85.250.124.0/24',   // Tranzila IP range
        '212.199.138.0/24',  // Tranzila IP range
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow in local/testing environments
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (!$this->isAllowedIp($clientIp)) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the IP is in the allowed list.
     */
    protected function isAllowedIp(string $ip): bool
    {
        foreach ($this->allowedIps as $allowedIp) {
            if ($this->ipInRange($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range.
     */
    protected function ipInRange(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - (int) $mask)) - 1);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
