<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordEntry;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * Afficher le dossier médical du patient authentifié
     */
    public function index()
    {
        try {
            $patientId = auth()->id();

            // Récupérer ou créer le dossier médical
            $medicalRecord = MedicalRecord::with(['entries.doctor', 'entries.consultation'])
                ->firstOrCreate(['patient_id' => $patientId]);

            return response()->json([
                'success' => true,
                'data' => $medicalRecord
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du dossier médical',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une entrée spécifique du dossier médical
     */
    public function showEntry($entryId)
    {
        try {
            $patientId = auth()->id();

            // Vérifier que l'entrée appartient bien au dossier médical du patient
            $entry = MedicalRecordEntry::whereHas('medicalRecord', function($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
                ->with(['doctor', 'consultation'])
                ->findOrFail($entryId);

            // Récupérer les documents associés
            $documents = $entry->getMedia('medical_documents');

            return response()->json([
                'success' => true,
                'data' => [
                    'entry' => $entry,
                    'documents' => $documents->map(function($doc) {
                        return [
                            'id' => $doc->id,
                            'name' => $doc->name,
                            'file_name' => $doc->file_name,
                            'mime_type' => $doc->mime_type,
                            'size' => $doc->size,
                            'url' => $doc->getUrl(),
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'entrée médicale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function allEntriesWithMedia()
    {
        try {
            $patientId = auth()->id();

            $medicalRecord = \App\Models\MedicalRecord::with(['entries.doctor', 'entries.consultation'])
                ->where('patient_id', $patientId)
                ->first();

            if (!$medicalRecord) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $entries = $medicalRecord->entries->map(function ($entry) {
                $documents = $entry->getMedia('medical_documents')->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_name' => $doc->file_name,
                        'mime_type' => $doc->mime_type,
                        'size' => $doc->size,
                        'url' => $doc->getUrl(),
                    ];
                });
                return [
                    'entry' => $entry,
                    'documents' => $documents
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $entries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées médicales',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
