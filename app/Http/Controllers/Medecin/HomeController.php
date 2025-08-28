<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class   HomeController extends Controller
{
    /**
     * Affiche les données du médecin avec l'utilisateur associé.
     */
    public function show()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $doctor = Doctor::with(['user.rejects','media','speciality'])->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'doctor' => $doctor,
            ],
        ], 200);
    }

    /**
     * Enregistre les données du médecin et de l'utilisateur.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user.full_name' => 'required|string|max:255',
            'user.password' => 'nullable|string|min:8',
            'doctor.speciality_id' => 'required|exists:specialities,id',
            'doctor.birth_date' => 'nullable|date',
            'doctor.gender' => 'nullable|in:male,female,other',
            'doctor.national_id' => 'nullable|string|max:50',
            'doctor.license_number' => 'required|string|unique:doctors,license_number',
            'doctor.qualifications' => 'nullable|string',
            'doctor.years_of_experience' => 'nullable|integer',
            'doctor.office_phone' => 'nullable|string|max:15',
            'doctor.office_address' => 'nullable|string|max:255',
            'doctor.hospital_affiliation' => 'nullable|string|max:255',
            'doctor.consultation_fee' => 'nullable|numeric',
            'documents' => 'nullable|array',
            'documents.*.path' => 'required_with:documents|string',
            'documents.*.name' => 'nullable|string',
            'documents.*.description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();

            // Mise à jour des données de l'utilisateur (sauf email)
            $user->update([
                'full_name' => $request->input('user.full_name', $user->full_name),
                'password' => $request->has('user.password') ? bcrypt($request->input('user.password')) : $user->password,
                'status' => 'to_validate', // Définir le statut à `to_validate`
            ]);

            // Création des données du médecin
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'speciality_id' => $request->input('doctor.speciality_id'),
                'birth_date' => $request->input('doctor.birth_date'),
                'gender' => $request->input('doctor.gender'),
                'national_id' => $request->input('doctor.national_id'),
                'license_number' => $request->input('doctor.license_number'),
                'qualifications' => $request->input('doctor.qualifications'),
                'years_of_experience' => $request->input('doctor.years_of_experience'),
                'office_phone' => $request->input('doctor.office_phone'),
                'office_address' => $request->input('doctor.office_address'),
                'hospital_affiliation' => $request->input('doctor.hospital_affiliation'),
                'consultation_fee' => $request->input('doctor.consultation_fee'),
            ]);

            // Gestion des documents
            if ($request->has('documents')) {
                foreach ($request->input('documents', []) as $document) {
                    if (isset($document['path']) && Storage::exists($document['path'])) {
                        $doctor->addMediaFromDisk($document['path'])
                            ->usingName($document['name'] ?? null)
                            ->withCustomProperties([
                                'description' => $document['description'] ?? null,
                                'type' => $document['type'] ?? 'document',
                            ])
                            ->toMediaCollection('doctor_documents');
                        Storage::delete($document['path']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil créé avec succès.',
                'data' => [
                    'user' => $user,
                    'doctor' => $doctor,
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
     * Met à jour les données du médecin et de l'utilisateur.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::with('user')->where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Médecin introuvable.',
            ], 404);
        }

        $request->validate([
            'user.full_name' => 'nullable|string|max:255',
            'user.email' => 'nullable|string|email|max:255|unique:users,email,' . $doctor->user_id,
            'user.password' => 'nullable|string|min:8',
            'doctor.speciality_id' => 'nullable|exists:specialities,id',
            'doctor.birth_date' => 'nullable|date',
            'doctor.gender' => 'nullable|in:male,female,other',
            'doctor.national_id' => 'nullable|string|max:50',
            'doctor.license_number' => 'nullable|string|unique:doctors,license_number,' . $doctor->id,
            'doctor.qualifications' => 'nullable|string',
            'doctor.years_of_experience' => 'nullable|integer',
            'doctor.office_phone' => 'nullable|string|max:15',
            'doctor.office_address' => 'nullable|string|max:255',
            'doctor.hospital_affiliation' => 'nullable|string|max:255',
            'doctor.consultation_fee' => 'nullable|numeric',
            'documents' => 'nullable|array',
            'documents.*.path' => 'required_with:documents|string',
            'documents.*.name' => 'nullable|string',
            'documents.*.description' => 'nullable|string',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer|exists:media,id',
        ]);

        DB::beginTransaction();

        try {
            $user->update([
                'full_name' => $request->input('user.full_name', $user->full_name),
                'email' => $request->input('user.email', $user->email),
                'password' => $request->has('user.password') ? bcrypt($request->input('user.password')) : $user->password,
            ]);

            $doctor->update([
                'speciality_id' => $request->input('doctor.speciality_id', $doctor->speciality_id),
                'birth_date' => $request->input('doctor.birth_date', $doctor->birth_date),
                'gender' => $request->input('doctor.gender', $doctor->gender),
                'national_id' => $request->input('doctor.national_id', $doctor->national_id),
                'license_number' => $request->input('doctor.license_number', $doctor->license_number),
                'qualifications' => $request->input('doctor.qualifications', $doctor->qualifications),
                'years_of_experience' => $request->input('doctor.years_of_experience', $doctor->years_of_experience),
                'office_phone' => $request->input('doctor.office_phone', $doctor->office_phone),
                'office_address' => $request->input('doctor.office_address', $doctor->office_address),
                'hospital_affiliation' => $request->input('doctor.hospital_affiliation', $doctor->hospital_affiliation),
                'working_hours' => $request->input('doctor.working_hours', $doctor->working_hours),
                'consultation_fee' => $request->input('doctor.consultation_fee', $doctor->consultation_fee),
            ]);

            // Supprimer les documents
            if ($request->has('delete_documents')) {
                foreach ($request->input('delete_documents', []) as $mediaId) {
                    $media = $doctor->media()->where('id', $mediaId)->first();
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            // Ajouter de nouveaux documents
            if ($request->has('documents')) {
                foreach ($request->input('documents', []) as $document) {
                    if (isset($document['path']) && Storage::exists($document['path'])) {
                        $doctor->addMediaFromDisk($document['path'])
                            ->usingName($document['name'] ?? null)
                            ->withCustomProperties([
                                'description' => $document['description'] ?? null,
                                'type' => $document['type'] ?? 'document',
                            ])
                            ->toMediaCollection('doctor_documents');
                        Storage::delete($document['path']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'data' => [
                    'user' => $user,
                    'doctor' => $doctor,
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

    public function getSpecialities()
    {
        $specialities = Speciality::all();

        return response()->json([
            'success' => true,
            'data' => $specialities,
        ], 200);
    }
}
