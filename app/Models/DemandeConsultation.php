<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeConsultation extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'requested_by',
        'title',
        'description',
        'requested_start_time',
        'requested_end_time',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function rejects()
    {
        return $this->morphMany(Reject::class, 'rejectable');
    }
}
