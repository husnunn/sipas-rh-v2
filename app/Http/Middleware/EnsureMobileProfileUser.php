<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileProfileUser
{
    /**
     * Hanya pengguna dengan peran siswa atau guru yang boleh mengakses API profil mobile.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->isStudent() && ! $user->isTeacher())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah profil.',
            ], 403);
        }

        return $next($request);
    }
}
