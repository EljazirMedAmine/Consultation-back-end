<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs avec DataTables
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['role']);

            $query = $this->applyFilters($query, $request);
            $table = \Yajra\DataTables\DataTables::of($query);

            $table->editColumn('full_name', fn($user) => $user->full_name)
                ->editColumn('email', fn($user) => $user->email)
                ->editColumn('status', fn($user) => $user->status)
                ->addColumn('role', fn($user) => $user->role ? $user->role->name : '')
                ->addColumn('actions', fn($user) => $user->id);

            return $table->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Appliquer les filtres à la requête
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'telephone' => 'required|string|max:15',
            'status' => 'required|in:new,to_validate,validated,rejected,suspended',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id,
                'telephone' => $request->telephone,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $user->load('role'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($id)
    {
        try {
            $user = User::with(['role','doctor.media','patient','doctor.speciality' ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'error' => $e->getMessage(),
            ], 404);
        }
    }


    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'telephone' => 'required|string|max:15',
            'password' => 'sometimes|string|min:6',
            'role_id' => 'sometimes|exists:roles,id',
            'status' => 'sometimes|in:new,to_validate,validated,rejected,suspended',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);
            $user->update($request->only(['full_name', 'email', 'password', 'role_id', 'status','telephone']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $user->load('role'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createGuestUser(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        try {
            DB::beginTransaction();

            $temporaryPassword = $this->generateTemporaryPassword();

            $user = User::create([
                'full_name' => 'guest',
                'telephone' => '0000000000',
                'email' => $request->email,
                'password' => bcrypt($temporaryPassword),
                'role_id' => 1,
                'status' => 'new',
            ]);

            DB::commit();

            // Dispatch the job to send the email
            (new \App\Jobs\SendGuestUserEmail($user, $temporaryPassword))->handle();
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur invité créé avec succès',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur invité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateTemporaryPassword($length = 8)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }

    public function rejectUser(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $user = User::findOrFail($id);

            // Ajouter le motif de rejet
            $user->rejects()->create([
                'reason' => $request->input('reason'),
            ]);

            // Mettre à jour le statut de l'utilisateur
            $user->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur rejeté avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function validateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Mettre à jour le statut de l'utilisateur
            $user->update(['status' => 'validated']);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur validé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
