<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Wramirez83\Sjwt\SJWT;
use Wramirez83\Sjwt\UserAuth;

class AuthJWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jwt = SJWT::decode();
        if (gettype($jwt) == 'object') {
            return response()->json(['error' => 'Credenciales incorrectas'], 203);
        }
        $user = User::whereIdAndEmail($jwt[1]->id, $jwt[1]->email)->first();
        if ($user) {
            UserAuth::user()->setAtt($user->toArray());
        }
        if (! $user) {
            return response()->json(['error' => 'Usuario No Permitido'], 203);
        }
        if ((bool) $jwt[3] == true && ! (bool) $jwt[4] == true) {
            $request->merge(['jwt' => [$jwt[1]]]);

            return $next($request);
        } elseif ((bool) $jwt[4]) {
            return response()->json(['error' => 'Token Expirado'], 203);
        } elseif ((bool) $jwt[3] != true) {
            return response()->json(['error' => 'Sin Permisos'], 203);
        }
    }
}
