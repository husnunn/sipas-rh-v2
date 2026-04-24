<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMustChangePassword
{
    /**
     * For web requests, redirect users who must change their password.
     * API users get a flag in the login response instead.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->is('*/change-password', 'settings/*')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda harus mengganti password terlebih dahulu.',
                    'must_change_password' => true,
                ], 403);
            }

            return redirect()->route('settings.password');
        }

        return $next($request);
    }
}
