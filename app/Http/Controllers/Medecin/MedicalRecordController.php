<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordEntry;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    /**
     * Afficher tous les dossiers médicaux des patients du médecin
     */
    public function index(Request $request)
    {
        try {
            $doctorId = auth()->id();

            // Récupérer les patients qui ont consulté ce médecin
            $patients = User::whereHas('consultationsAsPatient')
                ->with(['medicalRecord' => function($query) {
                    $query->withCount('entries');
                }])
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $patients
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des dossiers médicaux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher le dossier médical d'un patient spécifique
     */
    public function show($patientId)
    {
        try {
            $doctorId = auth()->id();
            $patient=Patient::find($patientId);
            $user=User::findOrFail($patient->user_id);
            $patient = User::with('medicalRecord.entries.consultation',
                'medicalRecord.entries.doctor')
                ->findOrFail($user->id);

            // Si le patient n'a pas de dossier médical, on en crée un
            if (!$patient->medicalRecord) {
                $medicalRecord = MedicalRecord::create([
                    'patient_id' => $patientId
                ]);
                $patient->refresh();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'patient' => $patient,
                    'medical_record' => $patient->medicalRecord,
                    'entries' => $patient->medicalRecord->entries ?? []
                ]
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
     * Afficher les détails d'une entrée spécifique du dossier médical
     */
    public function getEntry($entryId)
    {
        try {
            $doctorId = auth()->id();

            $entry = MedicalRecordEntry::with(['consultation', 'doctor', 'medicalRecord.patient'])
                ->findOrFail($entryId);

            // Vérifier si le médecin a le droit de voir cette entrée
            $hasConsulted = Consultation::where('patient_id', $entry->medicalRecord->patient_id)
                ->exists();

            if (!$hasConsulted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas l\'autorisation de consulter cette entrée'
                ], 403);
            }

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

    /**
     * Créer une nouvelle entrée dans le dossier médical après une consultation
     */
    public function store(Request $request, $consultationId)
    {
        try {
            $doctorId = auth()->id();

            $request->validate([
                'notes' => 'nullable|string',
                'diagnosis' => 'nullable|string|max:255',
                'treatment' => 'nullable|string',
                'prescription' => 'nullable|string',
                'documents' => 'nullable|array',
                'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ]);

            // Vérifier si la consultation existe et appartient au médecin
            $consultation = Consultation::where('id', $consultationId)
                ->firstOrFail();

            DB::beginTransaction();

            // Vérifier si le patient a déjà un dossier médical, sinon le créer
            $medicalRecord = MedicalRecord::firstOrCreate(
                ['patient_id' => $consultation->patient_id]
            );

            // Vérifier si une entrée existe déjà pour cette consultation
            $entry = MedicalRecordEntry::firstOrNew([
                'consultation_id' => $consultationId
            ]);

            // Mettre à jour ou créer l'entrée
            $entry->medical_record_id = $medicalRecord->id;
            $entry->doctor_id = $doctorId;
            $entry->notes = $request->input('notes');
            $entry->diagnosis = $request->input('diagnosis');
            $entry->treatment = $request->input('treatment');
            $entry->prescription = $request->input('prescription');
            $entry->save();

            // Gérer les documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $entry->addMedia($document)
                        ->toMediaCollection('medical_documents');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrée médicale créée avec succès',
                'data' => $entry
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entrée médicale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une entrée existante du dossier médical
     */
    public function update(Request $request, $entryId)
    {
        try {
            $doctorId = auth()->id();

            $request->validate([
                'notes' => 'nullable|string',
                'diagnosis' => 'nullable|string|max:255',
                'treatment' => 'nullable|string',
                'prescription' => 'nullable|string',
                'documents' => 'nullable|array',
                'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
                'documents_to_delete' => 'nullable|array',
                'documents_to_delete.*' => 'integer'
            ]);

            $entry = MedicalRecordEntry::where('id', $entryId)
                ->where('doctor_id', $doctorId)
                ->firstOrFail();

            DB::beginTransaction();

            // Mettre à jour l'entrée
            $entry->notes = $request->input('notes', $entry->notes);
            $entry->diagnosis = $request->input('diagnosis', $entry->diagnosis);
            $entry->treatment = $request->input('treatment', $entry->treatment);
            $entry->prescription = $request->input('prescription', $entry->prescription);
            $entry->save();

            // Supprimer les documents si demandé
            if ($request->has('documents_to_delete')) {
                foreach ($request->input('documents_to_delete') as $docId) {
                    $media = $entry->getMedia('medical_documents')->where('id', $docId)->first();
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            // Ajouter de nouveaux documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $entry->addMedia($document)
                        ->toMediaCollection('medical_documents');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrée médicale mise à jour avec succès',
                'data' => $entry
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'entrée médicale',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
