<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia;

    protected $fillable = [
        'full_name',
        'telephone',
        'email',
        'password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getRoleListAttribute()
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function rejects()
    {
        return $this->morphMany(Reject::class, 'rejectable');
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }
    public function medicalRecord()
    {
        return $this->hasOne(\App\Models\MedicalRecord::class, 'patient_id');
    }

    public function medicalEntries()
    {
        return $this->hasMany(\App\Models\MedicalRecordEntry::class, 'doctor_id');
    }
}
