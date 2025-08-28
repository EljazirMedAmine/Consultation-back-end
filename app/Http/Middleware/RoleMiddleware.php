<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized - User not authenticated'
            ], 401);
        }

        $roles = explode('|', $role);

        // Vérification directe du rôle
        if (!in_array($user->role->code, $roles)) {
            return response()->json([
                'error' => 'Forbidden - Insufficient permissions',
                'required_role' => $role,
                'user_role' => $user->role->code
            ], 403);
        }

        return $next($request);
    }
}
