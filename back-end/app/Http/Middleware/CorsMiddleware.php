<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        $origin = $request->headers->get('Origin');

        if ($origin) {
            $response->headers->set(
                'Access-Control-Allow-Origin',
                $origin
            );
        }

        $response->headers->set(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS'
        );

        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, Accept, X-Requested-With'
        );

        $response->headers->set(
            'Access-Control-Allow-Credentials',
            'true'
        );

        return $response;
    }
}