<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\DemandeConsultation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DemandeConsultationController extends Controller
{
    /**
     * Crée une nouvelle demande de consultation.
     */



    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requested_start_time' => 'required|date',
            'requested_end_time' => 'required|date|after:requested_start_time',
        ]);

        $user = auth()->user();

        $demandeConsultation = DemandeConsultation::create([
            'patient_id' => $user->id,
            'doctor_id' => $request->doctor_id,
            'requested_by' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'requested_start_time' => Carbon::parse($request->requested_start_time)->format('Y-m-d H:i:s'),
            'requested_end_time' => Carbon::parse($request->requested_end_time)->format('Y-m-d H:i:s'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de consultation créée avec succès.',
            'data' => $demandeConsultation,
        ], 201);
    }
    /**
     * Liste toutes les demandes de consultation du patient authentifié.
     */
    public function index()
    {
        $user = auth()->user();

        $demandes = DemandeConsultation::where('patient_id', $user->id)
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $demandes,
        ], 200);
    }

    /**
     * Affiche les détails d'une demande de consultation spécifique.
     */
    public function show($id)
    {
        $user = auth()->user();

        $demande = DemandeConsultation::where('id', $id)
            ->where('patient_id', $user->id)
            ->with(['doctor', 'patient'])
            ->first();

        if (!$demande) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de consultation introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $demande,
        ], 200);
    }
}
