<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menolak request API tanpa header X-API-Key yang cocok dengan TICKET_API_KEY. */
class EnsureValidApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.ticket_api.key');
        $given = (string) $request->header('X-API-Key', '');

        // Jika TICKET_API_KEY belum diisi, semua request ditolak (aman secara default).
        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'API key tidak valid.'], 401);
        }

        return $next($request);
    }
}
