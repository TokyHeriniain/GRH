<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            return response()->json([
                'message' => 'Vous devez changer votre mot de passe'
            ], 403);
        }

        return $next($request);
    }

}
