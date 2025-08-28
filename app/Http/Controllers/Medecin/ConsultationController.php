<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DemandeConsultation;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    /**
     * Afficher la liste des consultations et demandes de consultation avec DataTables.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->id();
            $doctor = Doctor::where('user_id', $user)->first();
            $type = $request->input('type');

            // Si aucun type ou "all" : on combine consultations et demandes
            if (!$type || $type === 'all') {
                $consultations = Consultation::query()
                    ->where('doctor_id', $doctor->id)
                    ->with(['patient:id,full_name', 'doctor:id,full_name'])
                    ->select('id', 'title', 'description', 'start_time as time_start', 'end_time as time_end', 'status', 'patient_id', 'doctor_id', 'meet_url')
                    ->when($request->filled('status'), function($q) use($request) {
                        $q->where('status', $request->input('status'));
                    })
                    ->when($request->filled('start_date'), function($q) use($request) {
                        $q->whereDate('start_time', '>=', $request->input('start_date'));
                    })
                    ->when($request->filled('end_date'), function($q) use($request) {
                        $q->whereDate('end_time', '<=', $request->input('end_date'));
                    })
                    ->get()
                    ->map(function($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'status' => $item->status,
                            'start_time' => $item->time_start,
                            'end_time' => $item->time_end,
                            'patient_name' => $item->patient->full_name,
                            'type' => 'Consultation',
                            'meet_url' => $item->meet_url,
                            'actions' => $item->id,
                        ];
                    });

                $demandes = DemandeConsultation::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('status', '!=', 'approved')
                    ->with(['patient:id,full_name', 'doctor:id,full_name'])
                    ->select('id', 'title', 'description', 'requested_start_time as time_start', 'requested_end_time as time_end', 'status', 'patient_id', 'doctor_id')
                    ->when($request->filled('status'), function($q) use($request) {
                        $q->where('status', $request->input('status'));
                    })
                    ->when($request->filled('start_date'), function($q) use($request) {
                        $q->whereDate('requested_start_time', '>=', $request->input('start_date'));
                    })
                    ->when($request->filled('end_date'), function($q) use($request) {
                        $q->whereDate('requested_end_time', '<=', $request->input('end_date'));
                    })
                    ->get()
                    ->map(function($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'status' => $item->status,
                            'start_time' => $item->time_start,
                            'end_time' => $item->time_end,
                            'patient_name' => $item->patient->full_name,
                            'type' => 'Demande',
                            // Pas de meet_url ici
                            'actions' => $item->id,
                        ];
                    });

                $combined = $consultations->concat($demandes);

                return \Yajra\DataTables\DataTables::of($combined)->make(true);
            }
            // Si type = Demande
            elseif ($type === 'Demande') {
                $query = DemandeConsultation::query()
                    ->where('doctor_id', $doctor->id)
                    ->with(['patient:id,full_name', 'doctor:id,full_name']);

                if ($request->filled('status')) {
                    $query->where('status', $request->input('status'));
                }
                if ($request->filled('start_date')) {
                    $query->whereDate('requested_start_time', '>=', $request->input('start_date'));
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('requested_end_time', '<=', $request->input('end_date'));
                }

                $table = \Yajra\DataTables\DataTables::of($query);

                $table->editColumn('title', fn($item) => $item->title)
                    ->editColumn('status', fn($item) => $item->status)
                    ->editColumn('start_time', fn($item) => $item->requested_start_time)
                    ->editColumn('end_time', fn($item) => $item->requested_end_time)
                    ->addColumn('patient_name', fn($item) => $item->patient->full_name)
                    ->addColumn('type', fn($item) => 'Demande')
                    ->addColumn('actions', fn($item) => $item->id);
                // Pas de meet_url ici

                return $table->make(true);
            }
            // Si type = Consultation
            else {
                $query = Consultation::query()
                    ->where('doctor_id', $doctor->id)
                    ->with(['patient:id,full_name', 'doctor:id,full_name']);

                if ($request->filled('status')) {
                    $query->where('status', $request->input('status'));
                }
                if ($request->filled('start_date')) {
                    $query->whereDate('start_time', '>=', $request->input('start_date'));
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('end_time', '<=', $request->input('end_date'));
                }

                $table = \Yajra\DataTables\DataTables::of($query);

                $table->editColumn('title', fn($item) => $item->title)
                    ->editColumn('status', fn($item) => $item->status)
                    ->editColumn('start_time', fn($item) => $item->start_time)
                    ->editColumn('end_time', fn($item) => $item->end_time)
                    ->addColumn('patient_name', fn($item) => $item->patient->full_name)
                    ->addColumn('type', fn($item) => 'Consultation')
                    ->addColumn('meet_url', fn($item) => $item->meet_url)
                    ->addColumn('actions', fn($item) => $item->id);

                return $table->make(true);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des consultations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle consultation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        try {
            DB::beginTransaction();

            $consultation = Consultation::create($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consultation créée avec succès',
                'data' => $consultation,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une consultation.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'status' => 'sometimes|in:scheduled,completed,cancelled',
        ]);

        try {
            DB::beginTransaction();

            $consultation = Consultation::findOrFail($id);
            $consultation->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consultation mise à jour avec succès',
                'data' => $consultation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une consultation spécifique.
     */
    public function show($id)
    {
        try {
            $consultation = Consultation::with(['patient', 'doctor'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $consultation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation non trouvée',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Supprimer une consultation.
     */
    public function destroy($id)
    {
        try {
            $consultation = Consultation::findOrFail($id);
            $consultation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consultation supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la consultation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $demande = DemandeConsultation::findOrFail($id);

            // Mettre à jour le statut à "rejected"
            $demande->update([
                'status' => 'rejected',
            ]);

            // Ajouter la raison du rejet dans la table rejects avec la relation polymorphe
            $demande->rejects()->create([
                'reason' => $request->input('reason'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Demande de consultation rejetée avec succès.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet de la demande de consultation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Approuver une demande de consultation avec un lien de calendrier prédéfini
     */
    public function approveConsultation(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = auth()->id();
            $doctor=Doctor::where('user_id',$user)->first();
            $demande = DemandeConsultation::with(['patient', 'doctor'])
                ->where('doctor_id', $doctor->id)
                ->findOrFail($id);

            // Utiliser un lien statique pour le calendrier Google
            $calendarLink = "https://calendar.google.com/calendar/u/0/share?slt=1AQOvs_XTszGY-lYi94P4jVKxN90bnoMss4m0ze5jhthfeNPqODbNkR1VSaE0yQH8dzGjquzG1H59LQ";

            // Créer une consultation basée sur la demande
            $consultation = Consultation::create([
                'patient_id' => $demande->patient_id,
                'doctor_id' => $demande->doctor_id,
                'created_by' => $doctor->id,
                'title' => $demande->title,
                'description' => $demande->description,
                'start_time' => $demande->requested_start_time,
                'end_time' => $demande->requested_end_time,
                'meet_url' => $calendarLink,
                'status' => 'scheduled',
                'demande_consultation_id' => $demande->id,
            ]);

            // Mettre à jour le statut de la demande
            $demande->update(['status' => 'approved']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Demande de consultation validée avec succès.',
                'data' => [
                    'consultation' => $consultation,
                    'calendar_link' => $calendarLink
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la demande de consultation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Afficher les détails d'une consultation ou d'une demande par type et ID.
     */
    public function getDetailsByTypeAndId(Request $request, $type, $id)
    {
        try {
            $user = auth()->id();
            $doctor=Doctor::where('user_id',$user)->first();

            if ($type === 'consultation') {
                $item = Consultation::with(['patient', 'doctor', 'demandeConsultation'])
                    ->where('doctor_id', $doctor->id)
                    ->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'details' => $item,
                        'type' => 'Consultation'
                    ],
                ]);
            }
            elseif ($type === 'demande') {
                $item = DemandeConsultation::with(['patient', 'doctor', 'rejects'])
                    ->where('doctor_id', $doctor->id)
                    ->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'details' => $item,
                        'type' => 'Demande',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Type non reconnu. Utilisez "Consultation" ou "Demande".'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Récupérer la liste des consultations pour le calendrier.
     */
    public function myConsultationsList()
    {
        try {
            $user = auth()->id();
            $doctor=Doctor::where('user_id',$user)->first();

            $consultations = Consultation::where('doctor_id', $doctor->id)
                ->select('id', 'title', 'start_time', 'end_time', 'status')
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'title' => $consultation->title,
                        'start' => $consultation->start_time,
                        'end' => $consultation->end_time,
                        'status' => $consultation->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $consultations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des consultations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les événements récents.
     */
    public function recentEvents()
    {
        try {
            $user = auth()->id();
            $doctor=Doctor::where('user_id',$user)->first();

            $recentConsultations = Consultation::where('doctor_id', $doctor->id)
                ->with(['patient:id,full_name'])
                ->select('id', 'title', 'start_time', 'end_time', 'status', 'patient_id')
                ->orderBy('start_time', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'title' => $consultation->title,
                        'start_time' => $consultation->start_time,
                        'end_time' => $consultation->end_time,
                        'status' => $consultation->status,
                        'patient_name' => $consultation->patient->full_name,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentConsultations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des événements récents',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
