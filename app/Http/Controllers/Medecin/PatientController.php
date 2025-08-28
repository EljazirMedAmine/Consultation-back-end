<?php

namespace App\Http\Controllers\Medecin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Retourne la liste des patients d'un médecin sous forme de DataTable.
     */
    public function index(Request $request)
    {
        try {
            $doctorId = auth()->id();

            $patientIds = Consultation::distinct()
                ->pluck('patient_id')
                ->toArray();

            $query = Patient::whereIn('user_id', $patientIds)
                ->with('user:id,full_name');
            if ($request->filled('blood_group')) {
                $query->where('blood_group', $request->input('blood_group'));
            }
            if ($request->filled('gender')) {
                $query->where('gender', $request->input('gender'));
            }

            return \Yajra\DataTables\DataTables::of($query)
                ->addColumn('full_name', fn($patient) => $patient->user->full_name)
                ->editColumn('gender', fn($patient) => $patient->gender)
                ->editColumn('birth_day', fn($patient) => $patient->birth_day)
                ->addColumn('actions', fn($patient) => $patient->id)
                ->make(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des patients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
