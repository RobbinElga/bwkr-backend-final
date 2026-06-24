<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Pemakaian di route: ->middleware('role:super_admin,admin')
     * Lolos jika role user termasuk salah satu yang diizinkan.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Tidak terautentikasi.'], 401);
        }

        if (! in_array($user->role->value, $roles, true)) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke sumber daya ini.'], 403);
        }

        return $next($request);
    }
}
