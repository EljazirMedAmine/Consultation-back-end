<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;

class MedecinController extends Controller
{
    public function getDoctors()
    {
        $doctors = Doctor::with('speciality')
            ->select('id', 'user_id', 'speciality_id')
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'full_name' => $doctor->user->full_name,
                    'speciality' => $doctor->speciality->name,
                ];
            });

        return response()->json(['doctors' => $doctors]);
    }
    /**
     * Afficher les détails d'un médecin spécifique.
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with([
                'user',
                'speciality',
                'media'
            ])->findOrFail($id);
            $media = $doctor->media->map(function ($mediaItem) {
                return [
                    'id' => $mediaItem->id,
                    'name' => $mediaItem->name,
                    'file_name' => $mediaItem->file_name,
                    'mime_type' => $mediaItem->mime_type,
                    'url' => $mediaItem->getUrl(),
                ];
            });
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $doctor->id,
                    'full_name' => $doctor->user->full_name,
                    'email' => $doctor->user->email,
                    'telephone' => $doctor->user->telephone,
                    'speciality' => $doctor->speciality->name,
                    'birth_date' => $doctor->birth_date,
                    'gender' => $doctor->gender,
                    'qualifications' => $doctor->qualifications,
                    'years_of_experience' => $doctor->years_of_experience,
                    'office_phone' => $doctor->office_phone,
                    'office_address' => $doctor->office_address,
                    'hospital_affiliation' => $doctor->hospital_affiliation,
                    'working_hours' => $doctor->working_hours,
                    'consultation_fee' => $doctor->consultation_fee,
                    'avatar' => $doctor->getFirstMediaUrl() ?: null,
                    'media' => $media,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médecin non trouvé',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

}
