<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * Liste toutes les consultations du patient authentifié.
     */
    public function index(Request $request)
    {
        try {
            $patientId = auth()->id();
            $type = $request->input('type');

            // Si aucun type n'est spécifié ou si on veut afficher les deux types
            if (!$type || $type === 'all') {
                // Récupérer les consultations avec les champs nécessaires
                $consultations = Consultation::query()
                    ->where('patient_id', $patientId)
                    ->with(['patient:id,full_name', 'doctor:id,full_name'])
                    ->select('id', 'title', 'description', 'start_time as time_start', 'end_time as time_end', 'status', 'patient_id', 'doctor_id')
                    ->when($request->filled('status'), function ($q) use ($request) {
                        $q->where('status', $request->input('status'));
                    })
                    ->when($request->filled('start_date'), function ($q) use ($request) {
                        $q->whereDate('start_time', '>=', $request->input('start_date'));
                    })
                    ->when($request->filled('end_date'), function ($q) use ($request) {
                        $q->whereDate('end_time', '<=', $request->input('end_date'));
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'status' => $item->status,
                            'start_time' => $item->time_start,
                            'end_time' => $item->time_end,
                            'doctor_name' => $item->doctor->full_name,
                            'type' => 'Consultation',
                            'actions' => $item->id,
                        ];
                    });

                // Récupérer les demandes avec les champs nécessaires
                $demandes = \App\Models\DemandeConsultation::query()
                    ->where('patient_id', $patientId)
                    ->where('status', '!=', 'approved')
                    ->with(['patient:id,full_name', 'doctor:id,full_name'])
                    ->select('id', 'title', 'description', 'requested_start_time as time_start', 'requested_end_time as time_end', 'status', 'patient_id', 'doctor_id')
                    ->when($request->filled('status'), function ($q) use ($request) {
                        $q->where('status', $request->input('status'));
                    })
                    ->when($request->filled('start_date'), function ($q) use ($request) {
                        $q->whereDate('requested_start_time', '>=', $request->input('start_date'));
                    })
                    ->when($request->filled('end_date'), function ($q) use ($request) {
                        $q->whereDate('requested_end_time', '<=', $request->input('end_date'));
                    })
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'status' => $item->status,
                            'start_time' => $item->time_start,
                            'end_time' => $item->time_end,
                            'doctor_name' => $item->doctor->full_name,
                            'type' => 'Demande',
                            'actions' => $item->id,
                        ];
                    });

                // Combiner les deux collections
                $combined = $consultations->concat($demandes);

                return \Yajra\DataTables\DataTables::of($combined)->make(true);
            } // Sinon, utiliser le comportement existant pour un type spécifique
            elseif ($type === 'Demande') {
                $query = \App\Models\DemandeConsultation::query()
                    ->where('patient_id', $patientId)
                    ->where('status', '!=', 'approved')
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
                    ->addColumn('doctor_name', fn($item) => $item->doctor->full_name)
                    ->addColumn('type', fn($item) => 'Demande')
                    ->addColumn('actions', fn($item) => $item->id);

                return $table->make(true);
            } else {
                $query = Consultation::query()
                    ->where('patient_id', $patientId)
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
                    ->addColumn('doctor_name', fn($item) => $item->doctor->full_name)
                    ->addColumn('type', fn($item) => 'Consultation')
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
     * Retourne les horaires pleins d'un médecin.
     */
    public function getDoctorBusyHours($doctorId)
    {
        $busyHours = Consultation::where('doctor_id', $doctorId)
            ->where('status', 'scheduled')
            ->select('start_time', 'end_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $busyHours,
        ], 200);
    }

    /**
     * Afficher les détails d'une consultation ou d'une demande par type et ID pour le patient.
     */
    public function getDetailsByTypeAndId(Request $request, $type, $id)
    {
        try {
            $patientId = auth()->id();

            if ($type === 'consultation') {
                $item = Consultation::with(['patient', 'doctor', 'demandeConsultation'])
                    ->where('patient_id', $patientId)
                    ->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'details' => $item,
                        'type' => 'Consultation'
                    ],
                ]);
            } elseif ($type === 'demande') {
                $item = \App\Models\DemandeConsultation::with(['patient', 'doctor', 'rejects'])
                    ->where('patient_id', $patientId)
                    ->where('status', '!=', 'approved')
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
                'message' => 'Type non reconnu. Utilisez "consultation" ou "demande".'
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
     * Récupérer les 4 derniers événements (consultations ou demandes) du patient.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lastFourEvents()
    {
        try {
            $patientId = auth()->id();

            // Récupérer les 4 dernières consultations
            $consultations = Consultation::where('patient_id', $patientId)
                ->with('doctor:id,full_name')
                ->select('id', 'title', 'start_time', 'end_time', 'status', 'doctor_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'start_time' => $item->start_time,
                        'end_time' => $item->end_time,
                        'status' => $item->status,
                        'doctor_name' => $item->doctor->full_name,
                        'type' => 'Consultation',
                        'created_at' => $item->created_at,
                    ];
                });

            // Récupérer les 4 dernières demandes
            $demandes = \App\Models\DemandeConsultation::where('patient_id', $patientId)->where('status', '!=', 'approved')
                ->with('doctor:id,full_name')
                ->select('id', 'title', 'requested_start_time', 'requested_end_time', 'status', 'doctor_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'start_time' => $item->requested_start_time,
                        'end_time' => $item->requested_end_time,
                        'status' => $item->status,
                        'doctor_name' => $item->doctor->full_name,
                        'type' => 'Demande',
                        'created_at' => $item->created_at,
                    ];
                });

            // Combiner les deux collections
            $combinedEvents = $consultations->concat($demandes)
                ->sortByDesc('created_at')
                ->take(4)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $combinedEvents
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
