<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'created_by',
        'title',
        'description',
        'start_time',
        'end_time',
        'google_event_id',
        'meet_url',
        'status',
        'demande_consultation_id', // Ajout du champ
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function demandeConsultation()
    {
        return $this->belongsTo(DemandeConsultation::class, 'demande_consultation_id');
    }

    // app/Models/Consultation.php (ajouter cette méthode)

    public function medicalRecordEntry()
    {
        return $this->hasOne(MedicalRecordEntry::class);
    }
}
