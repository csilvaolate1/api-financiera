<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }
        $token = substr($header, 7);
        $user = User::query()->where('api_token', $token)->first();
        if (! $user) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }
        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);
        return $next($request);
    }
}
