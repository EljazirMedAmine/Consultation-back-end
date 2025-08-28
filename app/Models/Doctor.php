<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Doctor extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'doctors';
    protected $fillable = [
        'user_id',
        'speciality_id',
        'birth_date',
        'gender',
        'national_id',
        'license_number',
        'qualifications',
        'years_of_experience',
        'office_phone',
        'office_address',
        'hospital_affiliation',
        'working_hours',
        'consultation_fee',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'birth_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
}
