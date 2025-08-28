<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplteProfileController extends Controller
{
    /**
     * Retourne les données du patient avec l'utilisateur associé.
     */
    public function show()
    {
        $user = auth()->user()->load('rejects');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $patient = Patient::with(['user','user.rejects'])->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'patient' => $patient,
            ],
        ], 200);
    }

    /**
     * Enregistre les données du patient et met à jour l'utilisateur.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user.full_name' => 'required|string|max:255',
            'user.telephone' => 'nullable|string|max:15',
            'user.password' => 'nullable|string|min:8',
            'patient.birth_day' => 'nullable|date',
            'patient.gender' => 'nullable|in:male,female,other',
            'patient.blood_group' => 'nullable|string|max:3',
            'patient.allergies' => 'nullable|string',
            'patient.chronic_diseases' => 'nullable|string',
            'patient.current_medications' => 'nullable|string',
            'patient.weight' => 'nullable|numeric',
            'patient.height' => 'nullable|numeric',
            'patient.insurance_number' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();

            // Mise à jour des données de l'utilisateur
            $user->update([
                'full_name' => $request->input('user.full_name', $user->full_name),
                'telephone' => $request->input('user.telephone', $user->telephone),
                'password' => $request->has('user.password') ? bcrypt($request->input('user.password')) : $user->password,
                'status' => 'to_validate',
            ]);

            // Création des données du patient
            $patient = Patient::create([
                'user_id' => $user->id,
                'birth_day' => $request->input('patient.birth_day'),
                'gender' => $request->input('patient.gender'),
                'blood_group' => $request->input('patient.blood_group'),
                'allergies' => $request->input('patient.allergies'),
                'chronic_diseases' => $request->input('patient.chronic_diseases'),
                'current_medications' => $request->input('patient.current_medications'),
                'weight' => $request->input('patient.weight'),
                'height' => $request->input('patient.height'),
                'insurance_number' => $request->input('patient.insurance_number'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil créé avec succès.',
                'data' => [
                    'user' => $user,
                    'patient' => $patient,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la création du profil.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour les données du patient et de l'utilisateur.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::with('user')->where('user_id', $user->id)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient introuvable.',
            ], 404);
        }

        $request->validate([
            'user.full_name' => 'nullable|string|max:255',
            'user.telephone' => 'nullable|string|max:15',
            'user.password' => 'nullable|string|min:8',
            'patient.birth_day' => 'nullable|date',
            'patient.gender' => 'nullable|in:male,female,other',
            'patient.blood_group' => 'nullable|string|max:3',
            'patient.allergies' => 'nullable|string',
            'patient.chronic_diseases' => 'nullable|string',
            'patient.current_medications' => 'nullable|string',
            'patient.weight' => 'nullable|numeric',
            'patient.height' => 'nullable|numeric',
            'patient.insurance_number' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            // Mise à jour des données de l'utilisateur
            $user->update([
                'full_name' => $request->input('user.full_name', $user->full_name),
                'telephone' => $request->input('user.telephone', $user->telephone),
                'password' => $request->has('user.password') ? bcrypt($request->input('user.password')) : $user->password,
            ]);

            // Mise à jour des données du patient
            $patient->update([
                'birth_day' => $request->input('patient.birth_day', $patient->birth_day),
                'gender' => $request->input('patient.gender', $patient->gender),
                'blood_group' => $request->input('patient.blood_group', $patient->blood_group),
                'allergies' => $request->input('patient.allergies', $patient->allergies),
                'chronic_diseases' => $request->input('patient.chronic_diseases', $patient->chronic_diseases),
                'current_medications' => $request->input('patient.current_medications', $patient->current_medications),
                'weight' => $request->input('patient.weight', $patient->weight),
                'height' => $request->input('patient.height', $patient->height),
                'insurance_number' => $request->input('patient.insurance_number', $patient->insurance_number),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'data' => [
                    'user' => $user,
                    'patient' => $patient,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Échec lors de la mise à jour du profil.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
