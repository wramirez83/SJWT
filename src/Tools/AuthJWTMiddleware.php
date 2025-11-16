<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tools;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wramirez83\Sjwt\SJWT;
use Wramirez83\Sjwt\UserAuth;

/**
 * JWT Authentication Middleware for Laravel
 * 
 * Validates JWT tokens from request headers and attaches user data to the request.
 * 
 * Usage in routes:
 * ```php
 * Route::middleware([AuthJWTMiddleware::class])->group(function () {
 *     Route::get('/protected', function () {
 *         return UserAuth::user()->getAtt();
 *     });
 * });
 * ```
 */
class AuthJWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $jwt = SJWT::decode();

        // Check for errors
        if (isset($jwt->error) && $jwt->error === true) {
            return $this->errorResponse('Credenciales incorrectas: ' . ($jwt->message ?? 'Token inválido'), 401);
        }

        // Validate token
        if (!isset($jwt->valid) || !$jwt->valid) {
            if (isset($jwt->tokenExpired) && $jwt->tokenExpired) {
                return $this->errorResponse('Token Expirado', 401);
            }
            if (isset($jwt->signatureValid) && !$jwt->signatureValid) {
                return $this->errorResponse('Sin Permisos: Firma inválida', 403);
            }
            return $this->errorResponse('Token inválido', 401);
        }

        // Extract user data from payload
        $payload = $jwt->payload;
        
        // Optionally validate user exists (if User model is available)
        if (class_exists('\App\Models\User') && isset($payload->id) && isset($payload->email)) {
            $user = \App\Models\User::where('id', $payload->id)
                ->where('email', $payload->email)
                ->first();
            
            if (!$user) {
                return $this->errorResponse('Usuario No Permitido', 403);
            }
            
            // Set user attributes
            UserAuth::user()->setAtt($user->toArray());
        } else {
            // If no User model, just set payload data
            UserAuth::user()->setAtt((array)$payload);
        }

        // Attach JWT payload to request for use in controllers
        $request->merge(['jwt' => $payload]);

        return $next($request);
    }

    /**
     * Create a standardized error response
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
        ], $statusCode);
    }
}

