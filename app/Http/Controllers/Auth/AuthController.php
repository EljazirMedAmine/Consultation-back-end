<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register', 'refresh']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Identifiants invalides',
            ], 401);
        }

        return $this->respondWithTokens($token);
    }

    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:new,to_validate,validated,rejected,suspended',
        ]);

        try {
            $user = User::create([
                'full_name' => $request->full_name,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Utilisateur créé avec succès',
                'user' => $user->load('role'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'user' => $user->load('role'),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnecté avec succès',
        ]);
    }

    public function refresh(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            JWTAuth::setToken($request->refresh_token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refresh token invalide',
                ], 401);
            }

            $token = auth('api')->refresh();

            return $this->respondWithTokens($token);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de rafraîchir le token',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    protected function respondWithTokens($token)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        $user->load('role');

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'refresh_token' => auth('api')->setTTL(config('jwt.refresh_ttl', 20160))->tokenById($user->id),
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }
}
