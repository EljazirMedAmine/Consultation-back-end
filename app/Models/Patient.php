<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'birth_day',
        'gender',
        'email_address',
        'blood_group',
        'allergies',
        'chronic_diseases',
        'current_medications',
        'weight',
        'height',
        'insurance_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
